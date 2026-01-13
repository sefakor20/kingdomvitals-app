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

beforeEach(function (): void {
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
    $this->mainBranch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

test('authenticated user can view branches page', function (): void {
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
});

test('admin can create a new branch', function (): void {
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

test('non-admin cannot create a branch', function (): void {
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

test('admin can edit a branch', function (): void {
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

test('manager can edit their branch', function (): void {
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

test('staff cannot edit a branch', function (): void {
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

test('main branch cannot be deleted', function (): void {
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

test('admin can delete a non-main branch', function (): void {
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

test('branch name updates slug automatically on create', function (): void {
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

test('branch validation requires name and slug', function (): void {
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

test('branch slug must be unique', function (): void {
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

// ============================================
// NEW FUNCTIONALITY TESTS
// ============================================

test('creating a branch grants creator admin access', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'New Branch')
        ->set('slug', 'new-branch')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors();

    $newBranch = Branch::where('slug', 'new-branch')->first();
    expect($newBranch)->not->toBeNull();

    $access = UserBranchAccess::where('user_id', $user->id)
        ->where('branch_id', $newBranch->id)
        ->first();

    expect($access)->not->toBeNull();
    expect($access->role)->toBe(BranchRole::Admin);
});

test('branch-created event is dispatched after successful create', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Event Test Branch')
        ->set('slug', 'event-test-branch')
        ->set('status', 'active')
        ->call('store')
        ->assertDispatched('branch-created');
});

test('branch-updated event is dispatched after successful update', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertDispatched('branch-updated');
});

test('branch-deleted event is dispatched after successful delete', function (): void {
    $user = User::factory()->create();
    $secondaryBranch = Branch::factory()->create(['name' => 'To Delete']);

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
        ->call('delete')
        ->assertDispatched('branch-deleted');
});

// ============================================
// MODAL CANCEL OPERATION TESTS
// ============================================

test('cancel create modal closes modal and resets form', function (): void {
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
        ->set('name', 'Test Name')
        ->set('slug', 'test-slug')
        ->set('city', 'Test City')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('name', '')
        ->assertSet('slug', '')
        ->assertSet('city', '');
});

test('cancel edit modal closes modal and clears editing branch', function (): void {
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
        ->assertSet('editingBranch.id', $this->mainBranch->id)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingBranch', null);
});

test('cancel delete modal closes modal and clears deleting branch', function (): void {
    $user = User::factory()->create();
    $secondaryBranch = Branch::factory()->create();

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
        ->assertSet('deletingBranch.id', $secondaryBranch->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingBranch', null);
});

// ============================================
// AUTHORIZATION EDGE CASE TESTS
// ============================================

test('manager cannot delete a branch', function (): void {
    $user = User::factory()->create();
    $secondaryBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $secondaryBranch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('confirmDelete', $secondaryBranch)
        ->assertForbidden();
});

test('volunteer cannot edit a branch', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->assertForbidden();
});

test('volunteer cannot delete a branch', function (): void {
    $user = User::factory()->create();
    $secondaryBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $secondaryBranch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('confirmDelete', $secondaryBranch)
        ->assertForbidden();
});

test('user cannot edit branch without access', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User only has access to main branch
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // Try to edit a branch they don't have access to
    Livewire::test(BranchIndex::class)
        ->call('edit', $otherBranch)
        ->assertForbidden();
});

test('user cannot delete branch without access', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User only has access to main branch
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // Try to delete a branch they don't have access to
    Livewire::test(BranchIndex::class)
        ->call('confirmDelete', $otherBranch)
        ->assertForbidden();
});

// ============================================
// VALIDATION EDGE CASE TESTS
// ============================================

test('email must be valid format', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Test Branch')
        ->set('slug', 'test-branch')
        ->set('email', 'invalid-email')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['email']);
});

test('capacity must be zero or positive', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Test Branch')
        ->set('slug', 'test-branch')
        ->set('capacity', -1)
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['capacity']);
});

test('status must be valid status value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Test Branch')
        ->set('slug', 'test-branch')
        ->set('status', 'invalid-status')
        ->call('store')
        ->assertHasErrors(['status']);
});

test('slug does not auto-update when editing', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $originalSlug = $this->mainBranch->slug;

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->set('name', 'Completely New Name')
        ->assertSet('slug', $originalSlug); // Slug should remain unchanged
});

test('can update branch with same slug', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('edit', $this->mainBranch)
        ->set('name', 'Updated Name Same Slug')
        // Slug stays the same
        ->call('update')
        ->assertHasNoErrors();

    expect($this->mainBranch->fresh()->name)->toBe('Updated Name Same Slug');
});

test('all optional fields can be empty', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchIndex::class)
        ->call('create')
        ->set('name', 'Minimal Branch')
        ->set('slug', 'minimal-branch')
        ->set('status', 'active')
        // All other fields left empty/null
        ->call('store')
        ->assertHasNoErrors();

    $branch = Branch::where('slug', 'minimal-branch')->first();
    expect($branch)->not->toBeNull();
    // Optional fields can be null or empty string depending on how they're stored
    expect($branch->address)->toBeIn([null, '']);
    expect($branch->city)->toBeIn([null, '']);
    expect($branch->email)->toBeIn([null, '']);
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

test('branches are ordered with main first then by name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create additional branches with names that would sort differently
    Branch::factory()->create(['name' => 'Zebra Campus']);
    Branch::factory()->create(['name' => 'Alpha Campus']);

    $this->actingAs($user);

    // Verify order by checking that main branch name appears before Alpha in rendered output
    Livewire::test(BranchIndex::class)
        ->assertSeeInOrder(['Main Campus', 'Alpha Campus', 'Zebra Campus']);
});

test('statuses dropdown shows all status options', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->mainBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // The statuses should be available in the create modal
    Livewire::test(BranchIndex::class)
        ->call('create')
        ->assertSee('Active')
        ->assertSee('Inactive')
        ->assertSee('Pending')
        ->assertSee('Suspended');
});
