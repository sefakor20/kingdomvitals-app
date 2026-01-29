<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\PlanAccessService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create admin user with branch admin access
    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// BRANCH QUOTA ENFORCEMENT TESTS
// ============================================

it('allows branch creation when under quota', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Only 1 main branch exists (under quota of 5)
    $service = app(PlanAccessService::class);

    expect($service->canCreateBranch())->toBeTrue();
    expect(Branch::count())->toBe(1);
});

it('blocks branch creation when quota exceeded', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 2,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 1 more branch (2 total = at quota limit)
    Branch::factory()->create();

    $service = app(PlanAccessService::class);

    expect($service->canCreateBranch())->toBeFalse();
    // Branch should not be created if we check first
    expect(Branch::count())->toBe(2);
});

it('returns correct quota message when exceeded', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 1,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Only 1 main branch exists (at quota limit of 1)
    $service = app(PlanAccessService::class);
    $quota = $service->getBranchQuota();

    expect($quota['current'])->toBe(1);
    expect($quota['max'])->toBe(1);
    expect($quota['remaining'])->toBe(0);
    expect($service->canCreateBranch())->toBeFalse();
});

it('allows unlimited branch creation when max_branches is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_branches' => null, // Unlimited
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create many branches
    Branch::factory()->count(20)->create();

    $service = app(PlanAccessService::class);

    // Should still be able to create more
    expect($service->canCreateBranch())->toBeTrue();
    expect($service->getBranchQuota()['unlimited'])->toBeTrue();
    expect(Branch::count())->toBe(21); // 1 main + 20 created
});

it('displays quota information correctly', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 3 more branches (4 total = 80% usage)
    Branch::factory()->count(3)->create();

    $service = app(PlanAccessService::class);
    $quota = $service->getBranchQuota();

    expect($quota['current'])->toBe(4);
    expect($quota['max'])->toBe(5);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(1);
});

it('shows quota warning when approaching limit', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 3 more branches (4 total = 80% usage, at threshold)
    Branch::factory()->count(3)->create();

    $service = app(PlanAccessService::class);

    expect($service->isQuotaWarning('branches', 80))->toBeTrue();
});

it('does not show quota warning when under threshold', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Only 1 main branch (10% usage, well under threshold)
    $service = app(PlanAccessService::class);

    expect($service->isQuotaWarning('branches', 80))->toBeFalse();
});
