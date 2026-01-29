<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\BranchRole;
use App\Enums\InvoiceStatus;
use App\Livewire\Upgrade\PlanCheckout;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\PlatformPaystackService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {    // Load tenant routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create(['email' => 'admin@test.com']);
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Cache::flush();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// MOUNT / INITIALIZATION TESTS
// ============================================

it('redirects when plan is inactive', function (): void {
    $inactivePlan = SubscriptionPlan::create([
        'name' => 'Legacy',
        'slug' => 'legacy',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => false,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $inactivePlan])
        ->assertRedirect('/plans');
});

it('redirects when already on plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $plan->id]);
    Cache::flush();

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->assertRedirect('/plans');
});

it('mounts successfully with valid active plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->assertSet('plan.id', $plan->id)
        ->assertNoRedirect();
});

it('uses monthly billing cycle by default', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->assertSet('billingCycle', 'monthly');
});

it('accepts annual billing cycle from url', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan, 'cycle' => 'annual'])
        ->assertSet('billingCycle', 'annual');
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

it('returns monthly price for monthly cycle', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->assertSet('billingCycle', 'monthly');

    expect($component->instance()->selectedPrice)->toBe(100.0);
});

it('returns annual price for annual cycle', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan, 'cycle' => 'annual'])
        ->assertSet('billingCycle', 'annual');

    expect($component->instance()->selectedPrice)->toBe(1000.0);
});

it('calculates annual savings percentage', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000, // 1000 instead of 1200 = ~17% savings
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan]);

    // Annual savings: (100*12 - 1000) / (100*12) = 200/1200 = ~16.67%
    expect($component->instance()->annualSavings)->toBeGreaterThan(0);
});

it('returns correct billing cycle enum for monthly', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan]);

    expect($component->instance()->billingCycleEnum)->toBe(BillingCycle::Monthly);
});

it('returns correct billing cycle enum for annual', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan, 'cycle' => 'annual']);

    expect($component->instance()->billingCycleEnum)->toBe(BillingCycle::Annual);
});

// ============================================
// PAYMENT FLOW TESTS
// ============================================

it('shows error when paystack not configured', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService as not configured
    $mock = Mockery::mock(PlatformPaystackService::class);
    $mock->shouldReceive('isConfigured')->andReturn(false);
    $mock->shouldReceive('getPublicKey')->andReturn('');
    app()->instance(PlatformPaystackService::class, $mock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->call('initiatePayment')
        ->assertSet('errorMessage', 'Payment system is not configured. Please contact support.');
});

it('initiates payment successfully', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService as configured
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    $paystackMock->shouldReceive('initializeTransaction')->andReturn([
        'success' => true,
        'data' => ['authorization_url' => 'https://paystack.com/pay'],
        'reference' => 'REF123',
    ]);
    app()->instance(PlatformPaystackService::class, $paystackMock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->call('initiatePayment')
        ->assertDispatched('open-paystack');
});

it('handles payment initiation failure', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService as configured but failing
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    $paystackMock->shouldReceive('initializeTransaction')->andReturn([
        'success' => false,
        'error' => 'Network error',
    ]);
    app()->instance(PlatformPaystackService::class, $paystackMock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->call('initiatePayment')
        ->assertSet('isProcessing', false)
        ->assertNotSet('errorMessage', null);
});

it('handles payment success', function (): void {
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

    $this->tenant->update(['subscription_id' => $oldPlan->id]);

    // Create invoice with paystack reference
    PlatformInvoice::create([
        'tenant_id' => $this->tenant->id,
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
        'metadata' => ['paystack_reference' => 'success-ref'],
    ]);

    // Mock PaystackService
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    $paystackMock->shouldReceive('verifyTransaction')->andReturn([
        'success' => true,
        'data' => [
            'id' => 12345,
            'status' => 'success',
            'amount' => 10000,
        ],
    ]);
    app()->instance(PlatformPaystackService::class, $paystackMock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $newPlan])
        ->call('handlePaymentSuccess', 'success-ref')
        ->assertSet('showSuccess', true)
        ->assertSet('isProcessing', false)
        ->assertDispatched('upgrade-complete');
});

it('handles payment success failure', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService with failed verification
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    $paystackMock->shouldReceive('verifyTransaction')->andReturn([
        'success' => false,
        'error' => 'Transaction not found',
    ]);
    app()->instance(PlatformPaystackService::class, $paystackMock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->call('handlePaymentSuccess', 'invalid-ref')
        ->assertSet('showSuccess', false)
        ->assertSet('isProcessing', false)
        ->assertNotSet('errorMessage', null);
});

it('handles payment cancellation', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    app()->instance(PlatformPaystackService::class, $paystackMock);

    Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan])
        ->call('handlePaymentClosed')
        ->assertSet('isProcessing', false)
        ->assertSet('errorMessage', 'Payment was cancelled. You can try again when ready.');
});

// ============================================
// COMPUTED PROPERTY ACCESS TESTS
// ============================================

it('paystackConfigured computed property returns service state', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService as configured
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(true);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('pk_test_123');
    app()->instance(PlatformPaystackService::class, $paystackMock);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan]);

    expect($component->instance()->paystackConfigured)->toBeTrue();
    expect($component->instance()->paystackPublicKey)->toBe('pk_test_123');
});

it('paystackConfigured returns false when not configured', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    // Mock PaystackService as not configured
    $paystackMock = Mockery::mock(PlatformPaystackService::class);
    $paystackMock->shouldReceive('isConfigured')->andReturn(false);
    $paystackMock->shouldReceive('getPublicKey')->andReturn('');
    app()->instance(PlatformPaystackService::class, $paystackMock);

    $component = Livewire::actingAs($this->admin)
        ->test(PlanCheckout::class, ['plan' => $plan]);

    expect($component->instance()->paystackConfigured)->toBeFalse();
});
