<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\PlanAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create admin user with branch admin access
    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Clear cache before each test
    Cache::flush();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// DASHBOARD QUOTA DATA TESTS
// These tests verify the PlanAccessService methods that power the dashboard quota display
// ============================================

it('returns hasAnyQuotaLimits correctly when plan has limits', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 100,
        'max_branches' => 3,
        'sms_credits_monthly' => 500,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // All quotas are limited
    expect($service->getMemberQuota()['unlimited'])->toBeFalse();
    expect($service->getBranchQuota()['unlimited'])->toBeFalse();
    expect($service->getSmsQuota()['unlimited'])->toBeFalse();
    expect($service->getStorageQuota()['unlimited'])->toBeFalse();
});

it('returns unlimited for member/branch/sms when plan has null values', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_members' => null, // Unlimited
        'max_branches' => null, // Unlimited
        'sms_credits_monthly' => null, // Unlimited
        'storage_quota_gb' => 999, // Storage column doesn't allow null
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
    expect($service->getBranchQuota()['unlimited'])->toBeTrue();
    expect($service->getSmsQuota()['unlimited'])->toBeTrue();
    // Storage is not unlimited since the column doesn't allow null
    expect($service->getStorageQuota()['unlimited'])->toBeFalse();
});

it('returns all unlimited when no plan assigned', function (): void {
    // No subscription plan assigned (defaults to unlimited)
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
    expect($service->getBranchQuota()['unlimited'])->toBeTrue();
    expect($service->getSmsQuota()['unlimited'])->toBeTrue();
    expect($service->getStorageQuota()['unlimited'])->toBeTrue();
});

it('returns correct member quota information', function (): void {
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

it('returns correct SMS quota information', function (): void {
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
    expect($quota['unlimited'])->toBeFalse();
});

it('returns correct branch quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_branches' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Main branch already exists (1 branch)
    $service = new PlanAccessService($this->tenant);
    $quota = $service->getBranchQuota();

    expect($quota['current'])->toBe(1);
    expect($quota['max'])->toBe(5);
    expect($quota['percent'])->toBe(20.0);
    expect($quota['remaining'])->toBe(4);
    expect($quota['unlimited'])->toBeFalse();
});

it('returns correct storage quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);
    $quota = $service->getStorageQuota();

    expect($quota['used'])->toBe(0.0); // No files uploaded
    expect($quota['max'])->toBe(10);
    expect($quota['percent'])->toBe(0.0);
    expect($quota['remaining'])->toBe(10.0);
    expect($quota['unlimited'])->toBeFalse();
});

// ============================================
// QUOTA WARNING THRESHOLD TESTS
// ============================================

it('shows quota warning when above 80 percent threshold', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 9 members (90% usage - above 80% threshold)
    Member::factory()->count(9)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->isQuotaWarning('members', 80))->toBeTrue();
    expect($service->getMemberQuota()['percent'])->toBe(90.0);
    expect($service->getMemberQuota()['remaining'])->toBe(1);
});

it('does not show quota warning when below 80 percent threshold', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 members (50% usage - below 80% threshold)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->isQuotaWarning('members', 80))->toBeFalse();
    expect($service->getMemberQuota()['percent'])->toBe(50.0);
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
    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
});

// ============================================
// PLAN NAME TESTS
// ============================================

it('returns correct plan name', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Professional Plan',
        'slug' => 'professional',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'max_members' => 200,
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan()->name)->toBe('Professional Plan');
});

it('returns null plan name when no plan assigned', function (): void {
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan())->toBeNull();
});

// ============================================
// QUOTA EXCEEDED TESTS
// ============================================

it('shows quota at 100 percent when limit reached', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create exactly 5 members (100% usage)
    Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);

    $service = new PlanAccessService($this->tenant);
    $quota = $service->getMemberQuota();

    expect($quota['current'])->toBe(5);
    expect($quota['max'])->toBe(5);
    expect($quota['percent'])->toBe(100.0);
    expect($quota['remaining'])->toBe(0);
    expect($service->canCreateMember())->toBeFalse();
});
