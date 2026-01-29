<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\ClusterType;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Household;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
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
// HOUSEHOLD QUOTA TESTS
// ============================================

it('allows household creation when under quota via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_households' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 households (under quota)
    Household::factory()->count(5)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateHousehold())->toBeTrue();
    expect(Household::count())->toBe(5);
});

it('blocks household creation when quota exceeded via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_households' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 households (at quota limit)
    Household::factory()->count(5)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateHousehold())->toBeFalse();
});

it('returns correct household quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_households' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 8 households (80% usage)
    Household::factory()->count(8)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);
    $quota = $service->getHouseholdQuota();

    expect($quota['current'])->toBe(8);
    expect($quota['max'])->toBe(10);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(2);
});

it('allows unlimited household creation when max_households is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_households' => null,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 50 households
    Household::factory()->count(50)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateHousehold())->toBeTrue();
    expect($service->getHouseholdQuota()['unlimited'])->toBeTrue();
});

it('shows household quota warning when approaching limit', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_households' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 8 households (80% usage)
    Household::factory()->count(8)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->isQuotaWarning('households', 80))->toBeTrue();
});

// ============================================
// CLUSTER QUOTA TESTS
// ============================================

it('allows cluster creation when under quota via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_clusters' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 clusters (under quota)
    Cluster::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'cluster_type' => ClusterType::CellGroup,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateCluster())->toBeTrue();
    expect(Cluster::count())->toBe(5);
});

it('blocks cluster creation when quota exceeded via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_clusters' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 5 clusters (at quota limit)
    Cluster::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'cluster_type' => ClusterType::CellGroup,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateCluster())->toBeFalse();
});

it('returns correct cluster quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_clusters' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 8 clusters (80% usage)
    Cluster::factory()->count(8)->create([
        'branch_id' => $this->branch->id,
        'cluster_type' => ClusterType::CellGroup,
    ]);

    $service = app(PlanAccessService::class);
    $quota = $service->getClusterQuota();

    expect($quota['current'])->toBe(8);
    expect($quota['max'])->toBe(10);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(2);
});

it('allows unlimited cluster creation when max_clusters is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_clusters' => null,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 50 clusters
    Cluster::factory()->count(50)->create([
        'branch_id' => $this->branch->id,
        'cluster_type' => ClusterType::CellGroup,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateCluster())->toBeTrue();
    expect($service->getClusterQuota()['unlimited'])->toBeTrue();
});

// ============================================
// VISITOR QUOTA TESTS
// ============================================

it('allows visitor creation when under quota via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_visitors' => 100,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 50 visitors (under quota)
    Visitor::factory()->count(50)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateVisitor())->toBeTrue();
    expect(Visitor::count())->toBe(50);
});

it('blocks visitor creation when quota exceeded via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_visitors' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 10 visitors (at quota limit)
    Visitor::factory()->count(10)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateVisitor())->toBeFalse();
});

it('returns correct visitor quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_visitors' => 100,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 80 visitors (80% usage)
    Visitor::factory()->count(80)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);
    $quota = $service->getVisitorQuota();

    expect($quota['current'])->toBe(80);
    expect($quota['max'])->toBe(100);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(20);
});

it('allows unlimited visitor creation when max_visitors is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_visitors' => null,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 200 visitors
    Visitor::factory()->count(200)->create(['branch_id' => $this->branch->id]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateVisitor())->toBeTrue();
    expect($service->getVisitorQuota()['unlimited'])->toBeTrue();
});

// ============================================
// EQUIPMENT QUOTA TESTS
// ============================================

it('allows equipment creation when under quota via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_equipment' => 50,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 25 equipment items (under quota)
    Equipment::factory()->count(25)->create([
        'branch_id' => $this->branch->id,
        'category' => EquipmentCategory::Audio,
        'condition' => EquipmentCondition::Good,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateEquipment())->toBeTrue();
    expect(Equipment::count())->toBe(25);
});

it('blocks equipment creation when quota exceeded via service', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_equipment' => 10,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 10 equipment items (at quota limit)
    Equipment::factory()->count(10)->create([
        'branch_id' => $this->branch->id,
        'category' => EquipmentCategory::Audio,
        'condition' => EquipmentCondition::Good,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateEquipment())->toBeFalse();
});

it('returns correct equipment quota information', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_equipment' => 50,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 40 equipment items (80% usage)
    Equipment::factory()->count(40)->create([
        'branch_id' => $this->branch->id,
        'category' => EquipmentCategory::Audio,
        'condition' => EquipmentCondition::Good,
    ]);

    $service = app(PlanAccessService::class);
    $quota = $service->getEquipmentQuota();

    expect($quota['current'])->toBe(40);
    expect($quota['max'])->toBe(50);
    expect($quota['percent'])->toBe(80.0);
    expect($quota['remaining'])->toBe(10);
});

it('allows unlimited equipment creation when max_equipment is null', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'max_equipment' => null,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 100 equipment items
    Equipment::factory()->count(100)->create([
        'branch_id' => $this->branch->id,
        'category' => EquipmentCategory::Audio,
        'condition' => EquipmentCondition::Good,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->canCreateEquipment())->toBeTrue();
    expect($service->getEquipmentQuota()['unlimited'])->toBeTrue();
});

it('shows equipment quota warning when approaching limit', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'max_equipment' => 50,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create 40 equipment items (80% usage)
    Equipment::factory()->count(40)->create([
        'branch_id' => $this->branch->id,
        'category' => EquipmentCategory::Audio,
        'condition' => EquipmentCondition::Good,
    ]);

    $service = app(PlanAccessService::class);

    expect($service->isQuotaWarning('equipment', 80))->toBeTrue();
});
