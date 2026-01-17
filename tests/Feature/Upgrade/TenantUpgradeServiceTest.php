<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PlatformPaystackService;
use App\Services\TenantUpgradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

// ============================================
// INITIATE UPGRADE TESTS
// ============================================

it('cannot initiate upgrade to inactive plan', function (): void {
    $tenant = Tenant::create(['name' => 'Test Church', 'contact_email' => 'test@test.com']);

    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => false,
    ]);

    $service = app(TenantUpgradeService::class);

    $result = $service->initiateUpgrade(
        tenant: $tenant,
        newPlan: $plan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Selected plan is not available.');
});

it('cannot initiate upgrade to current plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'test@test.com',
        'subscription_id' => $plan->id,
    ]);

    $service = app(TenantUpgradeService::class);

    $result = $service->initiateUpgrade(
        tenant: $tenant,
        newPlan: $plan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('You are already on this plan.');
});

it('creates invoice when initiating upgrade', function (): void {
    $tenant = Tenant::create(['name' => 'Test Church', 'contact_email' => 'test@test.com']);

    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
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
        newPlan: $plan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeTrue();
    expect($result['invoice'])->not->toBeNull();
    expect((float) $result['invoice']->total_amount)->toBe(100.0);
    expect($result['invoice']->status)->toBe(InvoiceStatus::Sent);
    expect($result['reference'])->toBe('REF123');
});

it('creates annual invoice with correct pricing', function (): void {
    $tenant = Tenant::create(['name' => 'Test Church', 'contact_email' => 'test@test.com']);

    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
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
        newPlan: $plan,
        cycle: BillingCycle::Annual,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeTrue();
    expect((float) $result['invoice']->total_amount)->toBe(1000.0);
});

it('cancels invoice when paystack initialization fails', function (): void {
    $tenant = Tenant::create(['name' => 'Test Church', 'contact_email' => 'test@test.com']);

    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('initializeTransaction')->andReturn([
        'success' => false,
        'error' => 'Network error',
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->initiateUpgrade(
        tenant: $tenant,
        newPlan: $plan,
        cycle: BillingCycle::Monthly,
        email: 'test@test.com',
        callbackUrl: 'http://test.localhost/callback'
    );

    expect($result['success'])->toBeFalse();

    // Invoice should be cancelled
    $invoice = PlatformInvoice::where('tenant_id', $tenant->id)->first();
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);
});

// ============================================
// CAN UPGRADE TO TESTS
// ============================================

it('can upgrade to active plan', function (): void {
    $currentPlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);

    $newPlan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'subscription_id' => $currentPlan->id,
    ]);

    $service = app(TenantUpgradeService::class);

    expect($service->canUpgradeTo($tenant, $newPlan))->toBeTrue();
    expect($service->canUpgradeTo($tenant, $currentPlan))->toBeFalse();
});

it('cannot upgrade to inactive plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => false,
    ]);

    $tenant = Tenant::create(['name' => 'Test Church']);

    $service = app(TenantUpgradeService::class);

    expect($service->canUpgradeTo($tenant, $plan))->toBeFalse();
});

// ============================================
// COMPLETE UPGRADE TESTS
// ============================================

it('completes upgrade after successful payment verification', function (): void {
    $oldPlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);

    $newPlan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'subscription_id' => $oldPlan->id,
    ]);

    // Create invoice with paystack reference
    $invoice = PlatformInvoice::create([
        'tenant_id' => $tenant->id,
        'subscription_plan_id' => $newPlan->id,
        'billing_period' => 'Test Upgrade',
        'period_start' => now(),
        'period_end' => now()->addMonth(),
        'issue_date' => now(),
        'due_date' => now()->addDay(),
        'subtotal' => 100,
        'total_amount' => 100,
        'balance_due' => 100,
        'status' => InvoiceStatus::Sent,
        'currency' => 'GHS',
        'metadata' => ['paystack_reference' => 'test-upgrade-ref'],
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'success' => true,
        'data' => [
            'id' => 12345,
            'status' => 'success',
            'amount' => 10000,
        ],
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->completeUpgrade('test-upgrade-ref');

    expect($result['success'])->toBeTrue();
    expect($result['tenant']->subscription_id)->toBe($newPlan->id);
    expect($result['plan']->id)->toBe($newPlan->id);

    // Verify tenant was updated
    $tenant->refresh();
    expect($tenant->subscription_id)->toBe($newPlan->id);
});

it('returns already processed for paid invoice', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $tenant = Tenant::create([
        'name' => 'Test Church',
        'subscription_id' => $plan->id,
    ]);

    // Create already paid invoice
    $invoice = PlatformInvoice::create([
        'tenant_id' => $tenant->id,
        'subscription_plan_id' => $plan->id,
        'billing_period' => 'Test Upgrade',
        'period_start' => now(),
        'period_end' => now()->addMonth(),
        'issue_date' => now(),
        'due_date' => now()->addDay(),
        'subtotal' => 100,
        'total_amount' => 100,
        'balance_due' => 0,
        'amount_paid' => 100,
        'status' => InvoiceStatus::Paid,
        'currency' => 'GHS',
        'metadata' => ['paystack_reference' => 'already-paid-ref'],
    ]);

    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'success' => true,
        'data' => ['status' => 'success'],
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->completeUpgrade('already-paid-ref');

    expect($result['success'])->toBeTrue();
    expect($result['already_processed'])->toBeTrue();
});

it('fails when verification fails', function (): void {
    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'success' => false,
        'error' => 'Transaction not found',
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->completeUpgrade('invalid-ref');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Payment verification failed.');
});

it('fails when invoice not found', function (): void {
    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'success' => true,
        'data' => ['status' => 'success'],
    ]);
    app()->instance(PlatformPaystackService::class, $mock);

    $service = app(TenantUpgradeService::class);

    $result = $service->completeUpgrade('nonexistent-ref');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Invoice not found.');
});
