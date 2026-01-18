<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PlatformPaymentMethod;
use App\Enums\SupportLevel;
use App\Enums\TenantStatus;
use App\Livewire\SuperAdmin\Billing\InvoiceShow;
use App\Mail\PlatformInvoiceMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformPaymentReminder;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use App\Services\PlatformBillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function createEmailTestPlan(array $attributes = []): SubscriptionPlan
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

function createEmailTestTenant(string $id, ?string $email = 'test@example.com'): void
{
    DB::table('tenants')->insert([
        'id' => $id,
        'name' => 'Email Test Church',
        'contact_email' => $email,
        'status' => TenantStatus::Active->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    Mail::fake();
    PlatformInvoice::query()->delete();
});

describe('invoice email sending', function (): void {
    it('sends invoice email when invoice is sent', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-1', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-1')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        $billingService = app(PlatformBillingService::class);
        $result = $billingService->sendInvoice($invoice);

        expect($result)->toBeTrue();

        Mail::assertQueued(PlatformInvoiceMail::class, function ($mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });
    });

    it('does not send email when tenant has no contact email', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-2', null);

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-2')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        $billingService = app(PlatformBillingService::class);
        $result = $billingService->sendInvoiceEmail($invoice);

        expect($result)->toBeFalse();
        Mail::assertNothingQueued();
    });

    it('records email sent in reminders table', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-3', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-3')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        $billingService = app(PlatformBillingService::class);
        $billingService->sendInvoice($invoice);

        $this->assertDatabaseHas('platform_payment_reminders', [
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPaymentReminder::TYPE_INVOICE_SENT,
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'recipient_email' => 'church@example.com',
        ]);
    });

    it('can resend invoice email via Livewire component', function (): void {
        $admin = SuperAdmin::factory()->create();
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-4', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-4')
            ->sent()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('resendInvoiceEmail');

        Mail::assertQueued(PlatformInvoiceMail::class);
    });

    it('does not send email when resending without contact email', function (): void {
        $admin = SuperAdmin::factory()->create();
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-5', null);

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-5')
            ->sent()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('resendInvoiceEmail');

        Mail::assertNothingQueued();
    });
});

describe('payment confirmation email', function (): void {
    it('sends payment confirmation when payment is recorded', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-6', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-6')
            ->sent()
            ->create([
                'subscription_plan_id' => $plan->id,
                'total_amount' => 100,
                'balance_due' => 100,
            ]);

        $billingService = app(PlatformBillingService::class);
        $billingService->recordPayment($invoice, [
            'amount' => 100,
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'send_confirmation' => true,
        ]);

        Mail::assertQueued(PlatformPaymentReceivedMail::class);
    });

    it('can skip payment confirmation email', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-7', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-7')
            ->sent()
            ->create([
                'subscription_plan_id' => $plan->id,
                'total_amount' => 100,
                'balance_due' => 100,
            ]);

        $billingService = app(PlatformBillingService::class);
        $billingService->recordPayment($invoice, [
            'amount' => 100,
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'send_confirmation' => false,
        ]);

        Mail::assertNotQueued(PlatformPaymentReceivedMail::class);
    });

    it('records payment email in reminders table', function (): void {
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-8', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-8')
            ->sent()
            ->create([
                'subscription_plan_id' => $plan->id,
                'total_amount' => 100,
                'balance_due' => 100,
            ]);

        $billingService = app(PlatformBillingService::class);
        $billingService->recordPayment($invoice, [
            'amount' => 100,
            'payment_method' => PlatformPaymentMethod::BankTransfer,
        ]);

        $this->assertDatabaseHas('platform_payment_reminders', [
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPaymentReminder::TYPE_PAYMENT_RECEIVED,
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'recipient_email' => 'church@example.com',
        ]);
    });
});

describe('send invoice via livewire component', function (): void {
    it('sends invoice and email when status allows', function (): void {
        $admin = SuperAdmin::factory()->create();
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-9', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-9')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('sendInvoice');

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Sent);

        Mail::assertQueued(PlatformInvoiceMail::class);
    });

    it('marks invoice as sent even when no contact email', function (): void {
        $admin = SuperAdmin::factory()->create();
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-10', null);

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-10')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('sendInvoice');

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Sent);

        Mail::assertNothingQueued();
    });
});

describe('email history display', function (): void {
    it('displays email history on invoice show page', function (): void {
        $admin = SuperAdmin::factory()->create();
        $plan = createEmailTestPlan();
        createEmailTestTenant('email-test-11', 'church@example.com');

        $invoice = PlatformInvoice::factory()
            ->forTenant('email-test-11')
            ->sent()
            ->create(['subscription_plan_id' => $plan->id]);

        PlatformPaymentReminder::create([
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPaymentReminder::TYPE_INVOICE_SENT,
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'sent_at' => now(),
            'recipient_email' => 'church@example.com',
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->assertSee('Email History')
            ->assertSee('Invoice Sent')
            ->assertSee('church@example.com');
    });
});
