<?php

use App\Enums\BranchRole;
use App\Livewire\Branches\BranchIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
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

    // Create main branch
    $this->mainBranch = Branch::factory()->main()->create();
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('authenticated user can view branches page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get('/branches')
        ->assertOk()
        ->assertSeeLivewire(BranchIndex::class);
})->skip('Requires tenant routing setup');

test('admin can create a new branch', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'West Campus')
        ->set('slug', 'west-campus')
        ->set('city', 'Kumasi')
        ->set('state', 'Ashanti')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    expect(Branch::where('slug', 'west-campus')->exists())->toBeTrue();
});

test('non-admin cannot create a branch', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->assertForbidden();
});

test('admin can edit a branch', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->assertSet('showEditModal', true)
        ->set('name', 'Updated Main Campus')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    expect($this->mainBranch->fresh()->name)->toBe('Updated Main Campus');
});

test('manager can edit their branch', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->assertSet('showEditModal', true)
        ->set('name', 'Manager Updated Campus')
        ->call('update')
        ->assertHasNoErrors();

    expect($this->mainBranch->fresh()->name)->toBe('Manager Updated Campus');
});

test('staff cannot edit a branch', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->assertForbidden();
});

test('main branch cannot be deleted', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // Main branch delete should be forbidden by policy
    Livewire::test(BranchIndex::class)
        ->call('confirmDelete', $this->mainBranch)
        ->assertForbidden();
});

test('admin can delete a non-main branch', function () {
    $user = User::factory()->create();
    $secondaryBranch = Branch::factory()->create(['name' => 'Secondary Campus']);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $secondaryBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('confirmDelete', $secondaryBranch)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false);

    expect(Branch::find($secondaryBranch->id))->toBeNull();
});

test('branch name updates slug automatically on create', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'North Campus Location')
        ->assertSet('slug', 'north-campus-location');
});

test('branch validation requires name and slug', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', '')
        ->set('slug', '')
        ->call('store')
        ->assertHasErrors(['name', 'slug']);
});

test('branch slug must be unique', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Another Main')
        ->set('slug', 'main-campus') // Already exists
        ->call('store')
        ->assertHasErrors(['slug']);
});
