<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Livewire\Upgrade\PlansIndex;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {    // Load tenant routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create();
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
// PLANS INDEX COMPONENT LOGIC TESTS
// ============================================

it('returns only active plans from database', function (): void {
    $activePlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Legacy',
        'slug' => 'legacy',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => false,
    ]);

    $plans = SubscriptionPlan::where('is_active', true)
        ->orderBy('display_order')
        ->orderBy('price_monthly')
        ->get();

    expect($plans)->toHaveCount(1);
    expect($plans->first()->name)->toBe('Starter');
});

it('correctly identifies tenant current plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $plan->id]);

    expect($this->tenant->subscription_id)->toBe($plan->id);
});

it('toggles billing cycle', function (): void {
    $component = new PlansIndex;

    expect($component->billingCycle)->toBe('monthly');

    $component->toggleBillingCycle();
    expect($component->billingCycle)->toBe('annual');

    $component->toggleBillingCycle();
    expect($component->billingCycle)->toBe('monthly');
});

it('sets billing cycle directly', function (): void {
    $component = new PlansIndex;

    expect($component->billingCycle)->toBe('monthly');

    $component->setBillingCycle('annual');
    expect($component->billingCycle)->toBe('annual');

    $component->setBillingCycle('monthly');
    expect($component->billingCycle)->toBe('monthly');

    // Invalid value should not change
    $component->setBillingCycle('invalid');
    expect($component->billingCycle)->toBe('monthly');
});

it('orders plans by display order then price', function (): void {
    SubscriptionPlan::create([
        'name' => 'Enterprise',
        'slug' => 'enterprise',
        'price_monthly' => 500,
        'price_annual' => 5000,
        'display_order' => 3,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'price_monthly' => 25,
        'price_annual' => 250,
        'display_order' => 1,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'display_order' => 2,
        'is_active' => true,
    ]);

    $plans = SubscriptionPlan::where('is_active', true)
        ->orderBy('display_order')
        ->orderBy('price_monthly')
        ->get();

    expect($plans->first()->name)->toBe('Basic');
    expect($plans->last()->name)->toBe('Enterprise');
});

it('isCurrentPlan logic works correctly', function (): void {
    $currentPlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);

    $otherPlan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $currentPlan->id]);
    Cache::flush();

    // Test the underlying logic that isCurrentPlan uses
    $tenantPlanId = tenant()->subscription_id;

    expect($tenantPlanId === $currentPlan->id)->toBeTrue();
    expect($tenantPlanId === $otherPlan->id)->toBeFalse();
});

it('subscription plan has helper methods', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'max_members' => null,
        'max_branches' => 5,
        'storage_quota_gb' => 10,
        'is_active' => true,
    ]);

    expect($plan->hasUnlimitedMembers())->toBeTrue();
    expect($plan->hasUnlimitedBranches())->toBeFalse();
    expect($plan->getAnnualSavingsPercent())->toBeGreaterThan(0);
});

// ============================================
// LIVEWIRE COMPONENT TESTS
// ============================================

it('displays active plans ordered by display_order via Livewire', function (): void {
    SubscriptionPlan::create([
        'name' => 'Enterprise',
        'slug' => 'enterprise',
        'price_monthly' => 500,
        'price_annual' => 5000,
        'display_order' => 3,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'price_monthly' => 25,
        'price_annual' => 250,
        'display_order' => 1,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Legacy',
        'slug' => 'legacy',
        'price_monthly' => 30,
        'price_annual' => 300,
        'display_order' => 2,
        'is_active' => false,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->assertSee('Basic')
        ->assertSee('Enterprise')
        ->assertDontSee('Legacy');
});

it('identifies current plan correctly via Livewire', function (): void {
    $currentPlan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $currentPlan->id]);
    Cache::flush();

    $component = Livewire::actingAs($this->admin)
        ->test(PlansIndex::class);

    // Test the isCurrentPlan method
    expect($component->instance()->isCurrentPlan($currentPlan->id))->toBeTrue();
    expect($component->instance()->isCurrentPlan('non-existent-id'))->toBeFalse();
});

it('toggles billing cycle between monthly and annual via Livewire', function (): void {
    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->assertSet('billingCycle', 'monthly')
        ->call('setBillingCycle', 'annual')
        ->assertSet('billingCycle', 'annual')
        ->call('setBillingCycle', 'monthly')
        ->assertSet('billingCycle', 'monthly');
});

it('toggleBillingCycle method switches cycle via Livewire', function (): void {
    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->assertSet('billingCycle', 'monthly')
        ->call('toggleBillingCycle')
        ->assertSet('billingCycle', 'annual')
        ->call('toggleBillingCycle')
        ->assertSet('billingCycle', 'monthly');
});

it('redirects to checkout when selecting a different plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->call('selectPlan', $plan->id)
        ->assertRedirect("/plans/{$plan->id}/checkout?cycle=monthly");
});

it('does not redirect when selecting current plan', function (): void {
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
        ->test(PlansIndex::class)
        ->call('selectPlan', $plan->id)
        ->assertNoRedirect();
});

it('passes billing cycle to checkout redirect', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->set('billingCycle', 'annual')
        ->call('selectPlan', $plan->id)
        ->assertRedirect("/plans/{$plan->id}/checkout?cycle=annual");
});

it('ignores invalid billing cycle values via Livewire', function (): void {
    Livewire::actingAs($this->admin)
        ->test(PlansIndex::class)
        ->assertSet('billingCycle', 'monthly')
        ->call('setBillingCycle', 'invalid')
        ->assertSet('billingCycle', 'monthly')
        ->call('setBillingCycle', 'weekly')
        ->assertSet('billingCycle', 'monthly');
});

// ============================================
// COMPONENT ACCESS TESTS
// ============================================

it('plans computed property returns correct plans', function (): void {
    SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
        'display_order' => 1,
    ]);

    SubscriptionPlan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'price_monthly' => 50,
        'price_annual' => 500,
        'is_active' => true,
        'display_order' => 0,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PlansIndex::class);

    $plans = $component->instance()->plans;

    expect($plans)->toHaveCount(2);
    expect($plans->first()->name)->toBe('Basic'); // Ordered by display_order
    expect($plans->last()->name)->toBe('Pro');
});

it('currentPlan computed property returns tenant plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $plan->id]);
    Cache::flush();

    $component = Livewire::actingAs($this->admin)
        ->test(PlansIndex::class);

    expect($component->instance()->currentPlan)->not->toBeNull();
    expect($component->instance()->currentPlan->id)->toBe($plan->id);
});

it('currentPlanId computed property returns tenant subscription_id', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $plan->id]);
    Cache::flush();

    $component = Livewire::actingAs($this->admin)
        ->test(PlansIndex::class);

    expect($component->instance()->currentPlanId)->toBe($plan->id);
});
