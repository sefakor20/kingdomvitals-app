<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\PlanAccessService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create admin user with access
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
// MEMBER QUOTA ENFORCEMENT TESTS
// ============================================

it('allows member creation when under quota via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 members (under quota)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateMember())->toBeTrue();
    expect(Member::count())->toBe(5);
});

it('blocks member creation when quota exceeded via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 members (at quota limit)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateMember())->toBeFalse();
});

it('returns correct quota message when exceeded', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 3,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Fill quota
    Member::factory()->count(3)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);
    $quota = $service->getMemberQuota();

    expect($quota['current'])->toBe(3);
    expect($quota['max'])->toBe(3);
    expect($quota['remaining'])->toBe(0);
    expect($service->canCreateMember())->toBeFalse();
});

it('allows unlimited member creation when max_members is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_members' => null, // Unlimited
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 100 members
    Member::factory()->count(100)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    // Should still be able to create more
    expect($service->canCreateMember())->toBeTrue();
    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
});

it('displays quota information correctly', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 8 members (80% usage)
    Member::factory()->count(8)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);
    $quota = $service->getMemberQuota();

    expect($quota['current'])->toBe(8);
    expect($quota['max'])->toBe(10);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(2);
});

it('shows quota warning when approaching limit', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 8 members (80% usage, at threshold)
    Member::factory()->count(8)->create(['primary_branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->isQuotaWarning('members', 80))->toBeTrue();
});
