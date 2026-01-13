<?php

declare(strict_types=1);

use App\Enums\SuperAdminRole;
use App\Enums\SupportLevel;
use App\Livewire\SuperAdmin\Plans\PlanIndex;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use Livewire\Livewire;

it('can view plans page', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    $this->actingAs($owner, 'superadmin')
        ->get(route('superadmin.plans.index'))
        ->assertOk()
        ->assertSee('Subscription Plans');
});

it('shows all plans in the list', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan1 = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic-plan',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);
    $plan2 = SubscriptionPlan::create([
        'name' => 'Premium Plan',
        'slug' => 'premium-plan',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'storage_quota_gb' => 50,
        'support_level' => SupportLevel::Priority,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->assertSee('Basic Plan')
        ->assertSee('Premium Plan');
});

it('can create a new plan as owner', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Professional')
        ->set('slug', 'professional')
        ->set('description', 'For growing businesses')
        ->set('priceMonthly', '29.99')
        ->set('priceAnnual', '299.99')
        ->set('maxMembers', 50)
        ->set('maxBranches', 5)
        ->set('storageQuotaGb', 10)
        ->set('supportLevel', 'email')
        ->set('featuresInput', 'Feature 1, Feature 2, Feature 3')
        ->set('isActive', true)
        ->call('createPlan')
        ->assertSet('showCreateModal', false)
        ->assertDispatched('plan-created');

    $this->assertDatabaseHas('subscription_plans', [
        'name' => 'Professional',
        'slug' => 'professional',
        'price_monthly' => 29.99,
        'price_annual' => 299.99,
    ]);
});

it('auto-generates slug from name when creating', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('name', 'Enterprise Plus')
        ->assertSet('slug', 'enterprise-plus');
});

it('validates required fields when creating plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', '')
        ->set('slug', '')
        ->call('createPlan')
        ->assertHasErrors(['name', 'slug']);
});

it('validates unique slug when creating plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    SubscriptionPlan::create([
        'name' => 'Existing Plan',
        'slug' => 'existing-plan',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'New Plan')
        ->set('slug', 'existing-plan')
        ->set('priceMonthly', '20.00')
        ->set('priceAnnual', '200.00')
        ->set('storageQuotaGb', 5)
        ->call('createPlan')
        ->assertHasErrors(['slug']);
});

it('regular admin cannot create plans', function (): void {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'New Plan')
        ->set('slug', 'new-plan')
        ->set('priceMonthly', '10.00')
        ->set('priceAnnual', '100.00')
        ->set('storageQuotaGb', 5)
        ->call('createPlan')
        ->assertForbidden();
});

it('can open edit modal for a plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Edit Me',
        'slug' => 'edit-me',
        'description' => 'Test description',
        'price_monthly' => 25.00,
        'price_annual' => 250.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('openEditModal', $plan->id)
        ->assertSet('showEditModal', true)
        ->assertSet('name', 'Edit Me')
        ->assertSet('slug', 'edit-me')
        ->assertSet('editPlanId', $plan->id);
});

it('can update a plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('openEditModal', $plan->id)
        ->set('name', 'New Name')
        ->set('priceMonthly', '15.00')
        ->call('updatePlan')
        ->assertSet('showEditModal', false)
        ->assertDispatched('plan-updated');

    $this->assertDatabaseHas('subscription_plans', [
        'id' => $plan->id,
        'name' => 'New Name',
        'price_monthly' => 15.00,
    ]);
});

it('can delete a plan without subscribers', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Delete Me',
        'slug' => 'delete-me',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('confirmDelete', $plan->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deleteSubscriberCount', 0)
        ->call('deletePlan')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('plan-deleted');

    $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
});

it('shows subscriber count when trying to delete a plan with subscribers', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Has Subscribers',
        'slug' => 'has-subscribers',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    // Directly insert into tenants table to avoid Stancl tenancy events
    \Illuminate\Support\Facades\DB::table('tenants')->insert([
        'id' => 'subscriber-tenant-plan',
        'name' => 'Subscribed Tenant',
        'status' => 'active',
        'subscription_id' => $plan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('confirmDelete', $plan->id)
        ->assertSet('deleteSubscriberCount', 1)
        ->call('deletePlan')
        ->assertHasErrors(['delete']);

    $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
});

it('can toggle plan active status', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Toggle Me',
        'slug' => 'toggle-me',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_active' => true,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('toggleActive', $plan->id)
        ->assertDispatched('plan-status-changed');

    $plan->refresh();
    expect($plan->is_active)->toBeFalse();

    // Toggle back
    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('toggleActive', $plan->id);

    $plan->refresh();
    expect($plan->is_active)->toBeTrue();
});

it('can set a plan as default', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan1 = SubscriptionPlan::create([
        'name' => 'Plan 1',
        'slug' => 'plan-1',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_default' => true,
    ]);
    $plan2 = SubscriptionPlan::create([
        'name' => 'Plan 2',
        'slug' => 'plan-2',
        'price_monthly' => 20.00,
        'price_annual' => 200.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
        'is_default' => false,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('setAsDefault', $plan2->id)
        ->assertDispatched('plan-default-changed');

    $plan1->refresh();
    $plan2->refresh();

    expect($plan1->is_default)->toBeFalse();
    expect($plan2->is_default)->toBeTrue();
});

it('ensures only one default plan exists', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    // Create a plan as default
    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'First Default')
        ->set('slug', 'first-default')
        ->set('priceMonthly', '10.00')
        ->set('priceAnnual', '100.00')
        ->set('storageQuotaGb', 5)
        ->set('isDefault', true)
        ->call('createPlan');

    expect(SubscriptionPlan::where('is_default', true)->count())->toBe(1);

    // Create another plan as default
    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Second Default')
        ->set('slug', 'second-default')
        ->set('priceMonthly', '20.00')
        ->set('priceAnnual', '200.00')
        ->set('storageQuotaGb', 10)
        ->set('isDefault', true)
        ->call('createPlan');

    // Still only one default
    expect(SubscriptionPlan::where('is_default', true)->count())->toBe(1);
    expect(SubscriptionPlan::where('slug', 'second-default')->first()->is_default)->toBeTrue();
    expect(SubscriptionPlan::where('slug', 'first-default')->first()->is_default)->toBeFalse();
});

it('logs activity when creating plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Logged Plan')
        ->set('slug', 'logged-plan')
        ->set('priceMonthly', '10.00')
        ->set('priceAnnual', '100.00')
        ->set('storageQuotaGb', 5)
        ->call('createPlan');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'plan_created',
    ]);
});

it('logs activity when updating plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Update Log Test',
        'slug' => 'update-log-test',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('openEditModal', $plan->id)
        ->set('name', 'Updated Name')
        ->call('updatePlan');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'plan_updated',
    ]);
});

it('logs activity when deleting plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Delete Log Test',
        'slug' => 'delete-log-test',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('confirmDelete', $plan->id)
        ->call('deletePlan');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'plan_deleted',
    ]);
});

it('logs activity when toggling plan status', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Toggle Log Test',
        'slug' => 'toggle-log-test',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_active' => true,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('toggleActive', $plan->id);

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'plan_deactivated',
    ]);
});

it('logs activity when setting default plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Default Log Test',
        'slug' => 'default-log-test',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->call('setAsDefault', $plan->id);

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'plan_set_default',
    ]);
});

it('parses features from comma-separated input', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Features Test')
        ->set('slug', 'features-test')
        ->set('priceMonthly', '10.00')
        ->set('priceAnnual', '100.00')
        ->set('storageQuotaGb', 5)
        ->set('featuresInput', 'Feature A, Feature B, Feature C')
        ->call('createPlan');

    $plan = SubscriptionPlan::where('slug', 'features-test')->first();
    expect($plan->features)->toBe(['Feature A', 'Feature B', 'Feature C']);
});

it('shows canManage as false for regular admin', function (): void {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(PlanIndex::class)
        ->assertDontSee('Add Plan');
});

it('shows canManage as true for owner', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->assertSee('Add Plan');
});

it('shows default badge for default plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    SubscriptionPlan::create([
        'name' => 'Default Plan',
        'slug' => 'default-plan',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_default' => true,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->assertSee('Default Plan')
        ->assertSee('Default');
});

it('shows inactive badge for inactive plan', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    SubscriptionPlan::create([
        'name' => 'Inactive Plan',
        'slug' => 'inactive-plan',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_active' => false,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(PlanIndex::class)
        ->assertSee('Inactive Plan')
        ->assertSee('Inactive');
});
