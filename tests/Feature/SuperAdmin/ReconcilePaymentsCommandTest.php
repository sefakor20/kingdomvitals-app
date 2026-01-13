<?php

declare(strict_types=1);

use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use App\Enums\SupportLevel;
use App\Enums\TenantStatus;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Services\PlatformPaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function createTestPlanForReconcile(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => 'Test Plan '.uniqid(),
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'max_members' => 500,
        'max_branches' => 5,
        'storage_quota_gb' => 10,
        'sms_credits_monthly' => 100,
        'support_level' => SupportLevel::Community,
    ], $attributes));
}

function createTestTenantForReconcile(string $id, SubscriptionPlan $plan): string
{
    DB::table('tenants')->insert([
        'id' => $id,
        'name' => 'Test Tenant '.uniqid(),
        'status' => TenantStatus::Active->value,
        'subscription_id' => $plan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

describe('ReconcilePaymentsCommand', function (): void {
    beforeEach(function (): void {
        $this->plan = createTestPlanForReconcile();
        $this->tenantId = createTestTenantForReconcile('reconcile-test-'.uniqid(), $this->plan);
    });

    it('runs without errors when no payments exist', function (): void {
        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('Reconciliation complete');
    });

    it('detects stale pending payments older than 24 hours', function (): void {
        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->create();

        $payment = PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'status' => PlatformPaymentStatus::Pending,
        ]);

        // Manually update the created_at since create() overrides it
        DB::table('platform_payments')
            ->where('id', $payment->id)
            ->update(['created_at' => now()->subHours(48)]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('stale pending');
    });

    it('skips Paystack verification when credentials not configured', function (): void {
        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->create();

        PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'test_ref_123',
        ]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('Paystack credentials not configured');
    });

    it('verifies Paystack payments when credentials are configured', function (): void {
        // Set up platform Paystack credentials
        SystemSetting::set('default_paystack_secret_key', 'sk_test_xxxxx', 'integrations', true);
        SystemSetting::set('default_paystack_public_key', 'pk_test_xxxxx', 'integrations', true);
        SystemSetting::set('default_paystack_test_mode', true, 'integrations');

        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->sent()
            ->create();

        $payment = PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'test_ref_123',
        ]);

        // Mock Paystack API response
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'success',
                    'reference' => 'test_ref_123',
                    'amount' => 10000,
                    'currency' => 'GHS',
                ],
            ], 200),
        ]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('Verified successfully');

        $payment->refresh();
        expect($payment->status)->toBe(PlatformPaymentStatus::Successful);
    });

    it('marks failed Paystack payments correctly', function (): void {
        SystemSetting::set('default_paystack_secret_key', 'sk_test_xxxxx', 'integrations', true);
        SystemSetting::set('default_paystack_public_key', 'pk_test_xxxxx', 'integrations', true);

        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->sent()
            ->create();

        $payment = PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'failed_ref_456',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'failed',
                    'reference' => 'failed_ref_456',
                ],
            ], 200),
        ]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('Failed: failed');

        $payment->refresh();
        expect($payment->status)->toBe(PlatformPaymentStatus::Failed);
    });

    it('does not modify payments in dry-run mode', function (): void {
        SystemSetting::set('default_paystack_secret_key', 'sk_test_xxxxx', 'integrations', true);
        SystemSetting::set('default_paystack_public_key', 'pk_test_xxxxx', 'integrations', true);

        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->sent()
            ->create();

        $payment = PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'test_ref_dryrun',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        $this->artisan('billing:reconcile-payments --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('DRY RUN MODE');

        $payment->refresh();
        expect($payment->status)->toBe(PlatformPaymentStatus::Pending);
    });

    it('handles API errors gracefully', function (): void {
        SystemSetting::set('default_paystack_secret_key', 'sk_test_xxxxx', 'integrations', true);
        SystemSetting::set('default_paystack_public_key', 'pk_test_xxxxx', 'integrations', true);

        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->sent()
            ->create();

        PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'error_ref',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => false,
                'message' => 'Transaction not found',
            ], 404),
        ]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful();
    });

    it('detects payment discrepancies', function (): void {
        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->create([
                'total_amount' => 200.00,
                'amount_paid' => 150.00,
                'balance_due' => 50.00,
            ]);

        PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'status' => PlatformPaymentStatus::Successful,
            'paid_at' => now(),
        ]);

        $this->artisan('billing:reconcile-payments')
            ->assertSuccessful()
            ->expectsOutputToContain('payment discrepancies');
    });

    it('uses the days option to limit search range', function (): void {
        $invoice = PlatformInvoice::factory()
            ->forTenant($this->tenantId)
            ->create();

        // Old payment outside the search range
        PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $this->tenantId,
            'amount' => 100.00,
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::Paystack,
            'status' => PlatformPaymentStatus::Pending,
            'paystack_reference' => 'old_ref',
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('billing:reconcile-payments --days=3')
            ->assertSuccessful()
            ->expectsOutputToContain('Reconciling payments from the last 3 days');
    });
});

describe('PlatformPaystackService', function (): void {
    it('returns not configured when no credentials set', function (): void {
        $service = app(PlatformPaystackService::class);

        expect($service->isConfigured())->toBeFalse();
    });

    it('returns configured when credentials are set', function (): void {
        SystemSetting::set('default_paystack_secret_key', 'sk_test_xxxxx', 'integrations', true);

        // Clear the service singleton so it gets fresh credentials
        app()->forgetInstance(PlatformPaystackService::class);
        $service = app(PlatformPaystackService::class);

        expect($service->isConfigured())->toBeTrue();
    });

    it('generates unique platform references', function (): void {
        $service = app(PlatformPaystackService::class);

        $ref1 = $service->generateReference();
        $ref2 = $service->generateReference();

        expect($ref1)->toStartWith('PLATFORM-');
        expect($ref2)->toStartWith('PLATFORM-');
        expect($ref1)->not->toBe($ref2);
    });

    it('converts amounts to and from kobo', function (): void {
        expect(PlatformPaystackService::toKobo(100.50))->toBe(10050);
        expect(PlatformPaystackService::fromKobo(10050))->toBe(100.50);
    });
});
