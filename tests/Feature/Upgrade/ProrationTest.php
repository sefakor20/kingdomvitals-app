<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PlatformPaystackService;
use App\Services\ProrationService;
use App\Services\TenantUpgradeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    $this->currentPlan = SubscriptionPlan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->upgradePlan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 200,
        'price_annual' => 2000,
        'is_active' => true,
    ]);

    $this->downgradePlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

// ============================================
// PRORATION SERVICE INTEGRATION TESTS
// ============================================

it('applies proration when tenant has active billing period', function (): void {
    Carbon::setTestNow('2026-01-16');

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => $this->currentPlan->id,
        'billing_cycle' => 'monthly',
        'current_period_start' => Carbon::parse('2026-01-01'),
        'current_period_end' => Carbon::parse('2026-01-31'),
    ]);

    $prorationService = app(ProrationService::class);

    expect($prorationService->shouldApplyProration($tenant))->toBeTrue();

    $result = $prorationService->calculatePlanChange($tenant, $this->upgradePlan, BillingCycle::Monthly);

    expect($result['days_remaining'])->toBe(15);
    expect($result['change_type'])->toBe('upgrade');
    expect($result['old_plan_credit'])->toBeGreaterThan(0);
    expect($result['new_plan_cost'])->toBeGreaterThan(0);
    expect($result['amount_due'])->toBeGreaterThan(0);
});

it('does not apply proration for new subscription', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => null,
    ]);

    $prorationService = app(ProrationService::class);

    expect($prorationService->shouldApplyProration($tenant))->toBeFalse();
});

// ============================================
// UPGRADE INVOICE WITH PRORATION TESTS
// ============================================

it('creates invoice with proration credit for mid-month upgrade', function (): void {
    Carbon::setTestNow('2026-01-16');

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => $this->currentPlan->id,
        'billing_cycle' => 'monthly',
        'current_period_start' => Carbon::parse('2026-01-01'),
        'current_period_end' => Carbon::parse('2026-01-31'),
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('initializeTransaction')->andReturn([
        'success' => true,
        'data' => ['authorization_url' => 'https://paystack.com/pay'],
        'reference' => 'REF123',
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->initiateUpgrade(
        tenant: $tenant,
        newPlan: $this->upgradePlan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeTrue();

    $invoice = $result['invoice'];

    // Invoice should have proration credit
    expect((float) $invoice->proration_credit)->toBeGreaterThan(0);
    expect($invoice->previous_plan_id)->toBe($this->currentPlan->id);
    expect($invoice->change_type)->toBe('upgrade');

    // Total should be less than full price due to credit
    expect((float) $invoice->total_amount)->toBeLessThan((float) $this->upgradePlan->price_monthly);

    // Check invoice items
    $items = $invoice->items;
    expect($items)->toHaveCount(2);

    // First item should be credit (negative amount)
    $creditItem = $items->firstWhere('total', '<', 0);
    expect($creditItem)->not->toBeNull();
    expect($creditItem->description)->toContain('Credit');

    // Second item should be the new plan charge
    $chargeItem = $items->firstWhere('total', '>', 0);
    expect($chargeItem)->not->toBeNull();
    expect($chargeItem->description)->toContain('Pro');
});

it('creates invoice without proration for new subscription', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => null,
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('initializeTransaction')->andReturn([
        'success' => true,
        'data' => ['authorization_url' => 'https://paystack.com/pay'],
        'reference' => 'REF123',
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->initiateUpgrade(
        tenant: $tenant,
        newPlan: $this->upgradePlan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeTrue();

    $invoice = $result['invoice'];

    // No proration for new subscription
    expect((float) $invoice->proration_credit)->toBe(0.0);

    // Full price charged
    expect((float) $invoice->total_amount)->toBe((float) $this->upgradePlan->price_monthly);

    // Only one line item
    expect($invoice->items)->toHaveCount(1);
});

// ============================================
// DOWNGRADE WITH CREDIT TESTS
// ============================================

it('generates account credit for mid-month downgrade', function (): void {
    Carbon::setTestNow('2026-01-16');

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => $this->upgradePlan->id, // Start on higher plan
        'billing_cycle' => 'monthly',
        'current_period_start' => Carbon::parse('2026-01-01'),
        'current_period_end' => Carbon::parse('2026-01-31'),
        'account_credit' => 0,
    ]);

    $prorationService = app(ProrationService::class);

    $result = $prorationService->calculatePlanChange($tenant, $this->downgradePlan, BillingCycle::Monthly);

    expect($result['change_type'])->toBe('downgrade');
    expect($result['credit_generated'])->toBeGreaterThan(0);
    expect((float) $result['amount_due'])->toBe(0.0);
});

// ============================================
// BILLING PERIOD UPDATE TESTS
// ============================================

it('updates tenant billing period after upgrade completion', function (): void {
    Carbon::setTestNow('2026-01-16');

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => $this->currentPlan->id,
        'billing_cycle' => 'monthly',
        'current_period_start' => Carbon::parse('2026-01-01'),
        'current_period_end' => Carbon::parse('2026-01-31'),
    ]);

    // Create an invoice manually to simulate initiated upgrade
    $invoice = PlatformInvoice::create([
        'tenant_id' => $tenant->id,
        'subscription_plan_id' => $this->upgradePlan->id,
        'billing_period' => 'January 2026 Upgrade',
        'period_start' => now(),
        'period_end' => now()->addMonth(),
        'issue_date' => now(),
        'due_date' => now()->addDay(),
        'subtotal' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100,
        'amount_paid' => 0,
        'balance_due' => 100,
        'status' => InvoiceStatus::Sent,
        'currency' => 'GHS',
        'metadata' => [
            'paystack_reference' => 'REF123',
            'billing_cycle' => 'monthly',
        ],
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'success' => true,
        'data' => ['status' => 'success'],
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);
    $result = $service->completeUpgrade('REF123');

    expect($result['success'])->toBeTrue();

    $tenant->refresh();

    // Billing period should be updated
    expect($tenant->billing_cycle)->toBe('monthly');
    expect($tenant->current_period_start)->not->toBeNull();
    expect($tenant->current_period_end)->not->toBeNull();
    expect($tenant->subscription_id)->toBe($this->upgradePlan->id);
});

// ============================================
// TENANT CREDIT METHODS TESTS
// ============================================

it('applies credit to tenant account', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'account_credit' => 0,
    ]);

    $tenant->applyCredit(50.00);

    expect((float) $tenant->fresh()->account_credit)->toBe(50.0);
});

it('uses credit from tenant account', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'account_credit' => 100,
    ]);

    $used = $tenant->useCredit(30.00);

    expect($used)->toBe(30.0);
    expect((float) $tenant->fresh()->account_credit)->toBe(70.0);
});

it('uses only available credit amount', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'account_credit' => 50,
    ]);

    $used = $tenant->useCredit(100.00);

    expect($used)->toBe(50.0);
    expect((float) $tenant->fresh()->account_credit)->toBe(0.0);
});

// ============================================
// BILLING CYCLE HELPER TESTS
// ============================================

it('returns billing cycle enum from tenant', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'billing_cycle' => 'annual',
    ]);

    $cycle = $tenant->getBillingCycle();

    expect($cycle)->toBe(BillingCycle::Annual);
});

it('returns null when no billing cycle set', function (): void {
    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'billing_cycle' => null,
    ]);

    expect($tenant->getBillingCycle())->toBeNull();
});

it('checks if tenant is in active billing period', function (): void {
    Carbon::setTestNow('2026-01-15');

    $activeTenant = Tenant::create([
        'name' => 'Active Church',
        'contact_email' => 'active@test.com',
        'current_period_end' => Carbon::parse('2026-01-31'),
    ]);

    $expiredTenant = Tenant::create([
        'name' => 'Expired Church',
        'contact_email' => 'expired@test.com',
        'current_period_end' => Carbon::parse('2026-01-10'),
    ]);

    expect($activeTenant->isInActiveBillingPeriod())->toBeTrue();
    expect($expiredTenant->isInActiveBillingPeriod())->toBeFalse();
});
