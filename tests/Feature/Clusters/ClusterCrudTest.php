<?php

use App\Enums\BranchRole;
use App\Enums\ClusterType;
use App\Livewire\Clusters\ClusterIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
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
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view clusters page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/clusters")
        ->assertOk()
        ->assertSeeLivewire(ClusterIndex::class);
});

test('user without branch access cannot view clusters page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/clusters")
        ->assertForbidden();
});

test('unauthenticated user cannot view clusters page', function (): void {
    $this->get("/branches/{$this->branch->id}/clusters")
        ->assertRedirect('/login');
});

// ============================================
// VIEW CLUSTERS AUTHORIZATION TESTS
// ============================================

test('admin can view clusters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee($cluster->name);
});

test('manager can view clusters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee($cluster->name);
});

test('staff can view clusters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee($cluster->name);
});

test('volunteer can view clusters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee($cluster->name);
});

// ============================================
// CREATE CLUSTER AUTHORIZATION TESTS
// ============================================

test('admin can create a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Youth Fellowship')
        ->set('cluster_type', 'cell_group')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('cluster-created');

    expect(Cluster::where('name', 'Youth Fellowship')->exists())->toBeTrue();
});

test('manager can create a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Manager Cluster')
        ->set('cluster_type', 'house_fellowship')
        ->call('store')
        ->assertHasNoErrors();

    expect(Cluster::where('name', 'Manager Cluster')->exists())->toBeTrue();
});

test('staff can create a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Staff Cluster')
        ->set('cluster_type', 'zone')
        ->call('store')
        ->assertHasNoErrors();

    expect(Cluster::where('name', 'Staff Cluster')->exists())->toBeTrue();
});

test('volunteer cannot create a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE CLUSTER AUTHORIZATION TESTS
// ============================================

test('admin can update a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertSet('showEditModal', true)
        ->set('name', 'Updated Cluster')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('cluster-updated');

    expect($cluster->fresh()->name)->toBe('Updated Cluster');
});

test('manager can update a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertSet('showEditModal', true)
        ->set('name', 'Manager Updated')
        ->call('update')
        ->assertHasNoErrors();

    expect($cluster->fresh()->name)->toBe('Manager Updated');
});

test('staff can update a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertSet('showEditModal', true)
        ->set('name', 'Staff Updated')
        ->call('update')
        ->assertHasNoErrors();

    expect($cluster->fresh()->name)->toBe('Staff Updated');
});

test('volunteer cannot update a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertForbidden();
});

// ============================================
// DELETE CLUSTER AUTHORIZATION TESTS
// ============================================

test('admin can delete a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);
    $clusterId = $cluster->id;

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('cluster-deleted');

    expect(Cluster::find($clusterId))->toBeNull();
});

test('manager can delete a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);
    $clusterId = $cluster->id;

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Cluster::find($clusterId))->toBeNull();
});

test('staff cannot delete a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertForbidden();
});

test('volunteer cannot delete a cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search clusters by name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Youth Fellowship',
    ]);

    Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Elders Zone',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->set('search', 'Youth')
        ->assertSee('Youth Fellowship')
        ->assertDontSee('Elders Zone');
});

test('can filter clusters by type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Cluster::factory()->cellGroup()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Cell Group A',
    ]);

    Cluster::factory()->zone()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Zone B',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', 'cell_group')
        ->assertSee('Cell Group A')
        ->assertDontSee('Zone B');
});

test('can filter clusters by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Active Cluster',
        'is_active' => true,
    ]);

    Cluster::factory()->inactive()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Inactive Cluster',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active')
        ->assertSee('Active Cluster')
        ->assertDontSee('Inactive Cluster');
});

test('empty search shows all clusters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $clusters = Cluster::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->set('search', '');

    foreach ($clusters as $cluster) {
        $component->assertSee($cluster->name);
    }
});

// ============================================
// VALIDATION TESTS
// ============================================

test('name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->set('cluster_type', 'cell_group')
        ->call('store')
        ->assertHasErrors(['name']);
});

test('cluster type is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Cluster')
        ->set('cluster_type', '')
        ->call('store')
        ->assertHasErrors(['cluster_type']);
});

test('cluster type must be valid value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Cluster')
        ->set('cluster_type', 'invalid_type')
        ->call('store')
        ->assertHasErrors(['cluster_type']);
});

test('leader must exist in members table', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Cluster')
        ->set('cluster_type', 'cell_group')
        ->set('leader_id', 'non-existent-uuid')
        ->call('store')
        ->assertHasErrors(['leader_id']);
});

test('capacity must be positive if provided', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Cluster')
        ->set('cluster_type', 'cell_group')
        ->set('capacity', 0)
        ->call('store')
        ->assertHasErrors(['capacity']);
});

test('can create cluster with all optional fields empty', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Minimal Cluster')
        ->set('cluster_type', 'cell_group')
        ->call('store')
        ->assertHasNoErrors();

    $cluster = Cluster::where('name', 'Minimal Cluster')->first();
    expect($cluster)->not->toBeNull();
    expect($cluster->description)->toBeNull();
    expect($cluster->leader_id)->toBeNull();
});

test('can create cluster with all fields filled', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $leader = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $assistant = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Complete Cluster')
        ->set('cluster_type', 'house_fellowship')
        ->set('description', 'A complete cluster description')
        ->set('leader_id', $leader->id)
        ->set('assistant_leader_id', $assistant->id)
        ->set('meeting_day', 'Wednesday')
        ->set('meeting_time', '18:00')
        ->set('meeting_location', 'Church Hall')
        ->set('capacity', 20)
        ->set('is_active', true)
        ->set('notes', 'Some notes about the cluster')
        ->call('store')
        ->assertHasNoErrors();

    $cluster = Cluster::where('name', 'Complete Cluster')->first();
    expect($cluster)->not->toBeNull();
    expect($cluster->description)->toBe('A complete cluster description');
    expect($cluster->leader_id)->toBe($leader->id);
    expect($cluster->meeting_day)->toBe('Wednesday');
    expect($cluster->capacity)->toBe(20);
});

// ============================================
// MODAL CANCEL OPERATION TESTS
// ============================================

test('cancel create modal closes modal and resets form', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Test Cluster')
        ->set('cluster_type', 'cell_group')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('name', '')
        ->assertSet('cluster_type', '');
});

test('cancel edit modal closes modal and clears editing cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertSet('showEditModal', true)
        ->assertSet('editingCluster.id', $cluster->id)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingCluster', null);
});

test('cancel delete modal closes modal and clears deleting cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingCluster.id', $cluster->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingCluster', null);
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update cluster from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Cluster belongs to other branch
    $cluster = Cluster::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('edit', $cluster)
        ->assertForbidden();
});

test('user cannot delete cluster from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Cluster belongs to other branch
    $cluster = Cluster::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $cluster)
        ->assertForbidden();
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no clusters exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee('No clusters found');
});

test('cluster table displays cluster information correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $leader = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'TestLeader',
        'middle_name' => null,
        'last_name' => 'TestLastName',
    ]);

    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Cluster',
        'cluster_type' => ClusterType::CellGroup,
        'leader_id' => $leader->id,
        'meeting_day' => 'Wednesday',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee('Test Cluster')
        ->assertSee('Cell Group')
        ->assertSee('TestLeader')
        ->assertSee('TestLastName')
        ->assertSee('Wednesday')
        ->assertSee('Active');
});

test('member count is displayed for each cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    // Add some members to the cluster
    $members = Member::factory()->count(5)->create(['primary_branch_id' => $this->branch->id]);
    foreach ($members as $member) {
        $cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    }

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee('5');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Cluster');
});

test('create button is hidden for volunteers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canCreate)->toBeFalse();

    $component->call('create')->assertForbidden();
});

// ============================================
// CLUSTER WITH LEADER TESTS
// ============================================

test('available leaders shows active members from branch', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $activeMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $inactiveMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'inactive',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterIndex::class, ['branch' => $this->branch]);

    $availableLeaders = $component->instance()->availableLeaders;

    expect($availableLeaders->contains($activeMember))->toBeTrue();
    expect($availableLeaders->contains($inactiveMember))->toBeFalse();
});

test('can assign leader to cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $leader = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ClusterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Cluster with Leader')
        ->set('cluster_type', 'cell_group')
        ->set('leader_id', $leader->id)
        ->call('store')
        ->assertHasNoErrors();

    $cluster = Cluster::where('name', 'Cluster with Leader')->first();
    expect($cluster->leader_id)->toBe($leader->id);
});
