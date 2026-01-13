<?php

use App\Enums\BranchRole;
use App\Livewire\Children\AgeGroupIndex;
use App\Models\Tenant;
use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('authenticated user can view age groups page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/children/age-groups")
        ->assertOk()
        ->assertSeeLivewire(AgeGroupIndex::class);
});

test('admin can create a new age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Nursery')
        ->set('minAge', 0)
        ->set('maxAge', 2)
        ->set('color', '#4F46E5')
        ->set('isActive', true)
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('age-group-created');

    expect(AgeGroup::where('name', 'Nursery')->exists())->toBeTrue();
});

test('manager can create age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Toddlers')
        ->set('minAge', 2)
        ->set('maxAge', 3)
        ->call('store')
        ->assertHasNoErrors();

    expect(AgeGroup::where('name', 'Toddlers')->exists())->toBeTrue();
});

test('staff cannot create age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

test('admin can edit an age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $ageGroup = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('edit', $ageGroup)
        ->assertSet('showEditModal', true)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('age-group-updated');

    expect($ageGroup->fresh()->name)->toBe('Updated Name');
});

test('only admin can delete an age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $ageGroup = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $ageGroup)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('age-group-deleted');

    expect(AgeGroup::find($ageGroup->id))->toBeNull();
});

test('manager cannot delete an age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $ageGroup = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $ageGroup)
        ->assertForbidden();
});

test('validation requires name, min age, and max age', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->call('store')
        ->assertHasErrors(['name']);
});

test('max age must be greater than or equal to min age', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test')
        ->set('minAge', 5)
        ->set('maxAge', 3) // Invalid: max < min
        ->call('store')
        ->assertHasErrors(['maxAge']);
});

test('auto assign assigns children to matching age groups', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create age groups
    $nursery = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Nursery',
        'min_age' => 0,
        'max_age' => 2,
    ]);

    $preschool = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Preschool',
        'min_age' => 3,
        'max_age' => 5,
    ]);

    // Create children without age groups
    $child1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(1),
        'age_group_id' => null,
    ]);

    $child2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(4),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('autoAssignAll')
        ->assertDispatched('children-auto-assigned');

    expect($child1->fresh()->age_group_id)->toBe($nursery->id);
    expect($child2->fresh()->age_group_id)->toBe($preschool->id);
});

test('cancel create modal closes and resets form', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Test Name')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('name', '');
});

test('unassigned children count is computed correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create children without age groups
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(AgeGroupIndex::class, ['branch' => $this->branch]);

    expect($component->get('unassignedChildrenCount'))->toBe(3);
});
