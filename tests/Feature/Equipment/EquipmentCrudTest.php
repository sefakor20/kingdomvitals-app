<?php

use App\Enums\BranchRole;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Livewire\Equipment\EquipmentIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view equipment page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('equipment.index', $this->branch))
        ->assertSuccessful()
        ->assertSeeLivewire(EquipmentIndex::class);
});

test('user without branch access cannot view equipment page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('equipment.index', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user cannot view equipment page', function () {
    $this->get(route('equipment.index', $this->branch))
        ->assertRedirect();
});

// ============================================
// VIEW EQUIPMENT TESTS
// ============================================

test('admin can view equipment list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Equipment::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->equipment->count())->toBe(3);
});

test('volunteer can view equipment list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    Equipment::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->equipment->count())->toBe(2);
});

// ============================================
// CREATE EQUIPMENT TESTS
// ============================================

test('admin can create equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Microphone')
        ->set('category', EquipmentCategory::Audio->value)
        ->set('condition', EquipmentCondition::Good->value)
        ->set('serial_number', 'SN123456')
        ->set('manufacturer', 'Shure')
        ->set('location', 'Main Hall')
        ->call('store')
        ->assertDispatched('equipment-created');

    expect(Equipment::where('name', 'Test Microphone')->exists())->toBeTrue();
});

test('staff can create equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Speaker')
        ->set('category', EquipmentCategory::Audio->value)
        ->set('condition', EquipmentCondition::Excellent->value)
        ->call('store')
        ->assertDispatched('equipment-created');

    expect(Equipment::where('name', 'Test Speaker')->exists())->toBeTrue();
});

test('volunteer cannot create equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE EQUIPMENT TESTS
// ============================================

test('admin can update equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $equipment = Equipment::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Old Name',
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('edit', $equipment)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertDispatched('equipment-updated');

    expect($equipment->fresh()->name)->toBe('Updated Name');
});

test('volunteer cannot update equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('edit', $equipment)
        ->assertForbidden();
});

// ============================================
// DELETE EQUIPMENT TESTS
// ============================================

test('admin can delete equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $equipment)
        ->call('delete')
        ->assertDispatched('equipment-deleted');

    expect(Equipment::find($equipment->id))->toBeNull();
});

test('manager can delete equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $equipment)
        ->call('delete')
        ->assertDispatched('equipment-deleted');

    expect(Equipment::find($equipment->id))->toBeNull();
});

test('staff cannot delete equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $equipment)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search equipment by name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Equipment::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Shure SM58 Microphone',
    ]);

    Equipment::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'JBL Speaker',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->set('search', 'Shure');

    expect($component->instance()->equipment->count())->toBe(1);
    expect($component->instance()->equipment->first()->name)->toBe('Shure SM58 Microphone');
});

test('can filter equipment by category', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Equipment::factory()->audio()->create(['branch_id' => $this->branch->id]);
    Equipment::factory()->video()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->set('categoryFilter', EquipmentCategory::Audio->value);

    expect($component->instance()->equipment->count())->toBe(1);
    expect($component->instance()->equipment->first()->category)->toBe(EquipmentCategory::Audio);
});

test('can filter equipment by condition', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Equipment::factory()->excellent()->create(['branch_id' => $this->branch->id]);
    Equipment::factory()->outOfService()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->set('conditionFilter', EquipmentCondition::Excellent->value);

    expect($component->instance()->equipment->count())->toBe(1);
    expect($component->instance()->equipment->first()->condition)->toBe(EquipmentCondition::Excellent);
});

test('can filter equipment by availability', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Equipment::factory()->good()->create(['branch_id' => $this->branch->id]);
    Equipment::factory()->outOfService()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->set('availabilityFilter', 'out_of_service');

    expect($component->instance()->equipment->count())->toBe(1);
    expect($component->instance()->equipment->first()->condition)->toBe(EquipmentCondition::OutOfService);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('equipment name is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->set('category', EquipmentCategory::Audio->value)
        ->set('condition', EquipmentCondition::Good->value)
        ->call('store')
        ->assertHasErrors(['name' => 'required']);
});

test('equipment category is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Equipment')
        ->set('category', '')
        ->set('condition', EquipmentCondition::Good->value)
        ->call('store')
        ->assertHasErrors(['category' => 'required']);
});

// ============================================
// STATS TESTS
// ============================================

test('equipment stats are calculated correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Equipment::factory()->good()->create([
        'branch_id' => $this->branch->id,
        'purchase_price' => 1000,
    ]);
    Equipment::factory()->excellent()->create([
        'branch_id' => $this->branch->id,
        'purchase_price' => 2000,
    ]);
    Equipment::factory()->outOfService()->create([
        'branch_id' => $this->branch->id,
        'purchase_price' => 500,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch]);

    $stats = $component->instance()->equipmentStats;

    expect($stats['total'])->toBe(3);
    expect($stats['available'])->toBe(2);
    expect($stats['outOfService'])->toBe(1);
    expect((float) $stats['totalValue'])->toBe(3500.0);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('empty state is shown when no equipment exists', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->equipment->count())->toBe(0);
    $component->assertSee('No equipment found');
});

// ============================================
// CROSS-BRANCH ACCESS TESTS
// ============================================

test('user cannot update equipment from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('edit', $equipment)
        ->assertForbidden();
});

// ============================================
// MODAL TESTS
// ============================================

test('cancel create modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false);
});

test('cancel delete modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $equipment = Equipment::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $equipment)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false);
});

// ============================================
// CHECKOUT TESTS
// ============================================

test('staff can checkout available equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $equipment = Equipment::factory()->good()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('openCheckoutModal', $equipment)
        ->set('checkout_member_id', $member->id)
        ->set('checkout_date', now()->format('Y-m-d\TH:i'))
        ->set('expected_return_date', now()->addDays(7)->format('Y-m-d\TH:i'))
        ->set('checkout_purpose', 'Event use')
        ->call('processCheckout')
        ->assertDispatched('equipment-checked-out');

    expect($equipment->fresh()->isCheckedOut())->toBeTrue();
});

test('cannot checkout out-of-service equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $equipment = Equipment::factory()->outOfService()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('openCheckoutModal', $equipment);

    expect($component->get('showCheckoutModal'))->toBeFalse();
});

// ============================================
// RETURN TESTS
// ============================================

test('staff can return checked out equipment', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $equipment = Equipment::factory()->good()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Create an active checkout
    \App\Models\Tenant\EquipmentCheckout::factory()->approved()->create([
        'equipment_id' => $equipment->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    expect($equipment->fresh()->isCheckedOut())->toBeTrue();

    $this->actingAs($user);

    Livewire::test(EquipmentIndex::class, ['branch' => $this->branch])
        ->call('openReturnModal', $equipment)
        ->set('return_condition', EquipmentCondition::Good->value)
        ->set('return_notes', 'Returned in good condition')
        ->call('processReturn')
        ->assertDispatched('equipment-returned');

    expect($equipment->fresh()->isCheckedOut())->toBeFalse();
});
