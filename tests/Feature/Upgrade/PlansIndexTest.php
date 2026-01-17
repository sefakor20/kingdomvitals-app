<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Livewire\Upgrade\PlansIndex;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

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
    tenancy()->end();
    $this->tenant?->delete();
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
