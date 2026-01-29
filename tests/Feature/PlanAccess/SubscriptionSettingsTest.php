<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\PlanAccessService;
use Illuminate\Support\Facades\Cache;
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

    // Clear cache before each test
    Cache::flush();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// SUBSCRIPTION SETTINGS PAGE TESTS
// ============================================

it('returns plan information when plan is assigned', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Professional Plan',
        'slug' => 'professional',
        'description' => 'For growing churches',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'max_members' => 200,
        'max_branches' => 5,
        'sms_credits_monthly' => 1000,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan())->not->toBeNull();
    expect($service->getPlan()->name)->toBe('Professional Plan');
    expect($service->getPlan()->description)->toBe('For growing churches');
});

it('returns null plan when no plan is assigned', function (): void {
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan())->toBeNull();
});

it('returns correct support level from plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Enterprise Plan',
        'slug' => 'enterprise',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan()->support_level)->toBe(SupportLevel::Priority);
    expect($service->getPlan()->support_level->value)->toBe('priority');
});

it('returns all quotas correctly when plan has limits', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_members' => 50,
        'max_branches' => 2,
        'sms_credits_monthly' => 100,
        'storage_quota_gb' => 5,
        'max_households' => 25,
        'max_clusters' => 5,
        'max_visitors' => 100,
        'max_equipment' => 20,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // Member quota
    expect($service->getMemberQuota()['max'])->toBe(50);
    expect($service->getMemberQuota()['unlimited'])->toBeFalse();

    // Branch quota
    expect($service->getBranchQuota()['max'])->toBe(2);
    expect($service->getBranchQuota()['unlimited'])->toBeFalse();

    // SMS quota
    expect($service->getSmsQuota()['max'])->toBe(100);
    expect($service->getSmsQuota()['unlimited'])->toBeFalse();

    // Storage quota
    expect($service->getStorageQuota()['max'])->toBe(5);
    expect($service->getStorageQuota()['unlimited'])->toBeFalse();

    // Household quota
    expect($service->getHouseholdQuota()['max'])->toBe(25);
    expect($service->getHouseholdQuota()['unlimited'])->toBeFalse();

    // Cluster quota
    expect($service->getClusterQuota()['max'])->toBe(5);
    expect($service->getClusterQuota()['unlimited'])->toBeFalse();

    // Visitor quota
    expect($service->getVisitorQuota()['max'])->toBe(100);
    expect($service->getVisitorQuota()['unlimited'])->toBeFalse();

    // Equipment quota
    expect($service->getEquipmentQuota()['max'])->toBe(20);
    expect($service->getEquipmentQuota()['unlimited'])->toBeFalse();
});

it('returns all quotas as unlimited when no plan assigned', function (): void {
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getMemberQuota()['unlimited'])->toBeTrue();
    expect($service->getBranchQuota()['unlimited'])->toBeTrue();
    expect($service->getSmsQuota()['unlimited'])->toBeTrue();
    expect($service->getStorageQuota()['unlimited'])->toBeTrue();
    expect($service->getHouseholdQuota()['unlimited'])->toBeTrue();
    expect($service->getClusterQuota()['unlimited'])->toBeTrue();
    expect($service->getVisitorQuota()['unlimited'])->toBeTrue();
    expect($service->getEquipmentQuota()['unlimited'])->toBeTrue();
});

it('returns enabled modules from plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Standard Plan',
        'slug' => 'standard',
        'price_monthly' => 30.00,
        'price_annual' => 300.00,
        'enabled_modules' => ['members', 'donations', 'attendance', 'sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan()->enabled_modules)->toBe(['members', 'donations', 'attendance', 'sms']);
    expect($service->hasModule(\App\Enums\PlanModule::Members))->toBeTrue();
    expect($service->hasModule(\App\Enums\PlanModule::Donations))->toBeTrue();
});

it('returns features from plan', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['prayer_chain_sms', 'bulk_sms_scheduling', 'member_import'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->getPlan()->features)->toBe(['prayer_chain_sms', 'bulk_sms_scheduling', 'member_import']);
    expect($service->hasFeature('prayer_chain_sms'))->toBeTrue();
    expect($service->hasFeature('bulk_sms_scheduling'))->toBeTrue();
    expect($service->hasFeature('member_import'))->toBeTrue();
});

it('returns empty modules when plan has null enabled_modules', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'enabled_modules' => null, // null means all modules enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // When enabled_modules is null, hasModule returns true for all modules
    expect($service->hasModule(\App\Enums\PlanModule::Members))->toBeTrue();
    expect($service->hasModule(\App\Enums\PlanModule::Sms))->toBeTrue();
    expect($service->hasModule(\App\Enums\PlanModule::Reports))->toBeTrue();
});

it('returns empty features when plan has null features', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'features' => null, // null means all features enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // When features is null, hasFeature returns true for all features
    expect($service->hasFeature('prayer_chain_sms'))->toBeTrue();
    expect($service->hasFeature('any_feature'))->toBeTrue();
});
