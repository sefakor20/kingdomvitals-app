<?php

declare(strict_types=1);

use App\Enums\PlanModule;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Services\PlanAccessService;
use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Clear cache before each test
    Cache::flush();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// NULL/UNLIMITED QUOTA TESTS
// ============================================

it('returns unlimited quotas when plan is null', function (): void {
    // Tenant has no subscription plan
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    $memberQuota = $service->getMemberQuota();
    expect($memberQuota['unlimited'])->toBeTrue();
    expect($memberQuota['max'])->toBeNull();

    $branchQuota = $service->getBranchQuota();
    expect($branchQuota['unlimited'])->toBeTrue();

    $smsQuota = $service->getSmsQuota();
    expect($smsQuota['unlimited'])->toBeTrue();
});

it('returns unlimited quotas when quota field is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_members' => null, // Unlimited
        'max_branches' => null, // Unlimited
        'sms_credits_monthly' => null, // Unlimited
        'storage_quota_gb' => 999, // Large value for "unlimited" since column doesn't allow null
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
    expect($service->getBranchQuota()['unlimited'])->toBeTrue();
    expect($service->getSmsQuota()['unlimited'])->toBeTrue();
});

// ============================================
// MEMBER QUOTA TESTS
// ============================================

it('correctly calculates member quota percentage', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 members (50% usage)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);
    $quota = $service->getMemberQuota();

    expect($quota['current'])->toBe(5);
    expect($quota['max'])->toBe(10);
    expect($quota['percent'])->toBe(50.0);
    expect($quota['remaining'])->toBe(5);
    expect($quota['unlimited'])->toBeFalse();
});

it('allows member creation when under quota', function (): void {
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

    $service = new PlanAccessService($this->tenant);

    expect($service->canCreateMember())->toBeTrue();
});

it('blocks member creation when quota exceeded', function (): void {
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

    $service = new PlanAccessService($this->tenant);

    expect($service->canCreateMember())->toBeFalse();
});

// ============================================
// BRANCH QUOTA TESTS
// ============================================

it('correctly calculates branch quota percentage', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Already have 1 main branch, create 1 more (2 total = 40%)
    Branch::factory()->create();

    $service = new PlanAccessService($this->tenant);
    $quota = $service->getBranchQuota();

    expect($quota['current'])->toBe(2);
    expect($quota['max'])->toBe(5);
    expect($quota['percent'])->toBe(40.0);
    expect($quota['remaining'])->toBe(3);
});

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

    $service = new PlanAccessService($this->tenant);

    // Only 1 branch exists (main), under quota of 5
    expect($service->canCreateBranch())->toBeTrue();
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

    // Create 1 more branch (2 total = at limit)
    Branch::factory()->create();

    $service = new PlanAccessService($this->tenant);

    expect($service->canCreateBranch())->toBeFalse();
});

// ============================================
// SMS QUOTA TESTS
// ============================================

it('correctly calculates sms quota for current month', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'sms_credits_monthly' => 100,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 30 SMS logs this month
    SmsLog::factory()->count(30)->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $service = new PlanAccessService($this->tenant);
    $quota = $service->getSmsQuota();

    expect($quota['sent'])->toBe(30);
    expect($quota['max'])->toBe(100);
    expect($quota['percent'])->toBe(30.0);
    expect($quota['remaining'])->toBe(70);
});

it('allows sms when credits available', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'sms_credits_monthly' => 100,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->canSendSms())->toBeTrue();
    expect($service->canSendSms(50))->toBeTrue();
});

it('blocks sms when credits exhausted', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'sms_credits_monthly' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Use all 10 credits
    SmsLog::factory()->count(10)->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $service = new PlanAccessService($this->tenant);

    expect($service->canSendSms())->toBeFalse();
    expect($service->canSendSms(1))->toBeFalse();
});

// ============================================
// MODULE ACCESS TESTS
// ============================================

it('returns true for hasModule when modules is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Full Access',
        'slug' => 'full',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'enabled_modules' => null, // All modules enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasModule(PlanModule::Members))->toBeTrue();
    expect($service->hasModule(PlanModule::Donations))->toBeTrue();
    expect($service->hasModule('sms'))->toBeTrue();
});

it('returns true for hasModule when module in enabled_modules', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Limited Plan',
        'slug' => 'limited',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['members', 'donations'],
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasModule(PlanModule::Members))->toBeTrue();
    expect($service->hasModule('donations'))->toBeTrue();
});

it('returns false for hasModule when module not in enabled_modules', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Limited Plan',
        'slug' => 'limited',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['members'],
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasModule(PlanModule::Donations))->toBeFalse();
    expect($service->hasModule('sms'))->toBeFalse();
});

// ============================================
// FEATURE ACCESS TESTS
// ============================================

it('returns true for hasFeature when features is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Full Access',
        'slug' => 'full',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'features' => null, // All features enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('reports_export'))->toBeTrue();
    expect($service->hasFeature('advanced_analytics'))->toBeTrue();
});

it('returns true for hasFeature when feature in features array', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['reports_export', 'bulk_sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('reports_export'))->toBeTrue();
    expect($service->hasFeature('bulk_sms'))->toBeTrue();
});

it('returns false for hasFeature when feature not in features array', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_reports'],
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('reports_export'))->toBeFalse();
    expect($service->hasFeature('advanced_analytics'))->toBeFalse();
});

// ============================================
// QUOTA WARNING TESTS
// ============================================

it('shows quota warning when above threshold', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 9 members (90% usage, above 80% threshold)
    Member::factory()->count(9)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->isQuotaWarning('members', 80))->toBeTrue();
});

it('does not show quota warning when below threshold', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 members (50% usage, below 80% threshold)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->isQuotaWarning('members', 80))->toBeFalse();
});

it('does not show quota warning for unlimited quotas', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_members' => null, // Unlimited
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Even with many members, no warning for unlimited
    Member::factory()->count(100)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->isQuotaWarning('members', 80))->toBeFalse();
});

// ============================================
// CACHE INVALIDATION TESTS
// ============================================

it('invalidates cache correctly', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // First call should cache the count
    $quota1 = $service->getMemberQuota();
    expect($quota1['current'])->toBe(0);

    // Create a member
    Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Should still return cached value
    $quota2 = $service->getMemberQuota();
    expect($quota2['current'])->toBe(0);

    // Invalidate cache
    $service->invalidateCountCache('members');

    // Now should return updated count
    $quota3 = $service->getMemberQuota();
    expect($quota3['current'])->toBe(1);
});
