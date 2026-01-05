<?php

use App\Enums\BranchRole;
use App\Enums\HouseholdRole;
use App\Livewire\Households\HouseholdIndex;
use App\Livewire\Households\HouseholdShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
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

test('authenticated user with branch access can view households page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('households.index', $this->branch))
        ->assertSuccessful()
        ->assertSeeLivewire(HouseholdIndex::class);
});

test('user without branch access cannot view households page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('households.index', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user cannot view households page', function () {
    $this->get(route('households.index', $this->branch))
        ->assertRedirect();
});

// ============================================
// VIEW HOUSEHOLDS TESTS
// ============================================

test('admin can view household list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Household::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(HouseholdIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->households->count())->toBe(3);
});

test('can search households by name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Household::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Smith Family',
    ]);

    Household::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Jones Family',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->set('search', 'Smith');

    expect($component->instance()->households->count())->toBe(1);
    expect($component->instance()->households->first()->name)->toBe('Smith Family');
});

// ============================================
// CREATE HOUSEHOLD TESTS
// ============================================

test('admin can create household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $headMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Household')
        ->set('head_id', $headMember->id)
        ->set('address', '123 Test Street')
        ->call('store')
        ->assertHasNoErrors();

    expect(Household::where('name', 'Test Household')->exists())->toBeTrue();
});

test('staff can create household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'New Household')
        ->call('store')
        ->assertHasNoErrors();

    expect(Household::where('name', 'New Household')->exists())->toBeTrue();
});

test('volunteer cannot create household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE HOUSEHOLD TESTS
// ============================================

test('admin can update household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $household = Household::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Old Name',
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('edit', $household)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($household->fresh()->name)->toBe('Updated Name');
});

test('volunteer cannot update household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('edit', $household)
        ->assertForbidden();
});

// ============================================
// DELETE HOUSEHOLD TESTS
// ============================================

test('admin can delete household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $household)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Household::find($household->id))->toBeNull();
});

test('staff cannot delete household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $household)
        ->assertForbidden();
});

// ============================================
// HOUSEHOLD SHOW TESTS
// ============================================

test('can view household details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user)
        ->get(route('households.show', ['branch' => $this->branch, 'household' => $household]))
        ->assertSuccessful()
        ->assertSeeLivewire(HouseholdShow::class);
});

test('can view household members', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(HouseholdShow::class, [
        'branch' => $this->branch,
        'household' => $household,
    ]);

    expect($component->instance()->members->count())->toBe(3);
});

// ============================================
// ADD MEMBER TO HOUSEHOLD TESTS
// ============================================

test('admin can add member to household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdShow::class, [
        'branch' => $this->branch,
        'household' => $household,
    ])
        ->call('openAddMemberModal')
        ->set('memberSearch', $member->first_name)
        ->call('selectMember', $member->id)
        ->set('selectedRole', HouseholdRole::Head->value)
        ->call('addMember')
        ->assertHasNoErrors();

    expect($member->fresh()->household_id)->toBe($household->id);
    expect($member->fresh()->household_role)->toBe(HouseholdRole::Head);
});

test('volunteer cannot add member to household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(HouseholdShow::class, [
        'branch' => $this->branch,
        'household' => $household,
    ])
        ->call('openAddMemberModal')
        ->assertForbidden();
});

// ============================================
// REMOVE MEMBER FROM HOUSEHOLD TESTS
// ============================================

test('admin can remove member from household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Spouse,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdShow::class, [
        'branch' => $this->branch,
        'household' => $household,
    ])
        ->call('confirmRemoveMember', $member->id)
        ->call('removeMember')
        ->assertHasNoErrors();

    expect($member->fresh()->household_id)->toBeNull();
    expect($member->fresh()->household_role)->toBeNull();
});

// ============================================
// CHANGE MEMBER ROLE TESTS
// ============================================

test('admin can change member role in household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Child,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdShow::class, [
        'branch' => $this->branch,
        'household' => $household,
    ])
        ->call('openEditRoleModal', $member->id)
        ->set('editingRole', HouseholdRole::Spouse->value)
        ->call('updateRole')
        ->assertHasNoErrors();

    expect($member->fresh()->household_role)->toBe(HouseholdRole::Spouse);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('household name is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->call('store')
        ->assertHasErrors(['name' => 'required']);
});

test('household name max length is enforced', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', str_repeat('a', 101))
        ->call('store')
        ->assertHasErrors(['name' => 'max']);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('empty state is shown when no households exist', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->assertSee('No Households');
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

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
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

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(HouseholdIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $household)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false);
});
