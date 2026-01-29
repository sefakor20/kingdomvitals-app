<?php

use App\Enums\BranchRole;
use App\Enums\ClusterType;
use App\Livewire\Clusters\ClusterShow;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create a test cluster
    $this->cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view cluster show page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/clusters/{$this->cluster->id}")
        ->assertOk()
        ->assertSeeLivewire(ClusterShow::class);
});

test('user without branch access cannot view cluster show page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/clusters/{$this->cluster->id}")
        ->assertForbidden();
});

test('unauthenticated user cannot view cluster show page', function (): void {
    $this->get("/branches/{$this->branch->id}/clusters/{$this->cluster->id}")
        ->assertRedirect('/login');
});

// ============================================
// VIEW CLUSTER AUTHORIZATION TESTS
// ============================================

test('admin can view cluster details', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee($this->cluster->name);
});

test('manager can view cluster details', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee($this->cluster->name);
});

test('staff can view cluster details', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee($this->cluster->name);
});

test('volunteer can view cluster details', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee($this->cluster->name);
});

// ============================================
// DATA DISPLAY TESTS
// ============================================

test('cluster show page displays cluster information correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $leader = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Leader',
        'last_name' => 'Person',
    ]);

    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Youth Cell Group',
        'cluster_type' => ClusterType::CellGroup,
        'description' => 'A youth-focused cell group',
        'leader_id' => $leader->id,
        'meeting_day' => 'Friday',
        'meeting_time' => '18:30',
        'meeting_location' => 'Church Hall A',
        'capacity' => 15,
        'is_active' => true,
        'notes' => 'Some notes here',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->assertSee('Youth Cell Group')
        ->assertSee('Cell Group')
        ->assertSee('A youth-focused cell group')
        ->assertSee('Leader')
        ->assertSee('Person')
        ->assertSee('Friday')
        ->assertSee('Church Hall A')
        ->assertSee('15')
        ->assertSee('Active')
        ->assertSee('Some notes here');
});

test('cluster show displays not assigned for missing leader', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'leader_id' => null,
        'assistant_leader_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->assertSee('Not assigned');
});

test('cluster members are displayed in the members section', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->cluster->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee('John')
        ->assertSee('Doe')
        ->assertSee('Member');
});

test('empty state is shown when cluster has no members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->assertSee('No members in this cluster');
});

// ============================================
// INLINE EDITING TESTS
// ============================================

test('admin can edit cluster inline', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create a cluster with known valid data
    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'meeting_time' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Updated Cluster Name')
        ->set('description', 'Updated description')
        ->call('save')
        ->assertSet('editing', false)
        ->assertDispatched('cluster-updated');

    $cluster->refresh();
    expect($cluster->name)->toBe('Updated Cluster Name');
    expect($cluster->description)->toBe('Updated description');
});

test('manager can edit cluster inline', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    // Create a cluster with known valid data
    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'meeting_time' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Manager Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($cluster->fresh()->name)->toBe('Manager Updated');
});

test('staff can edit cluster inline', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create a cluster with known valid data
    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'meeting_time' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Staff Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($cluster->fresh()->name)->toBe('Staff Updated');
});

test('volunteer cannot edit cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('edit')
        ->assertForbidden();
});

test('cancel editing resets to view mode', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Changed Name')
        ->call('cancel')
        ->assertSet('editing', false);

    // Verify name was not changed
    expect($this->cluster->fresh()->name)->toBe($this->cluster->name);
});

test('edit mode fills form with current cluster data', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Name',
        'description' => 'Original Description',
        'meeting_day' => 'Monday',
        'capacity' => 25,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->call('edit')
        ->assertSet('name', 'Original Name')
        ->assertSet('description', 'Original Description')
        ->assertSet('meeting_day', 'Monday')
        ->assertSet('capacity', 25);
});

// ============================================
// MEMBER MANAGEMENT TESTS
// ============================================

test('admin can add member to cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('openAddMemberModal')
        ->assertSet('showAddMemberModal', true)
        ->set('selectedMemberId', $member->id)
        ->set('selectedMemberRole', 'member')
        ->call('addMember')
        ->assertSet('showAddMemberModal', false)
        ->assertDispatched('member-added');

    expect($this->cluster->members()->where('member_id', $member->id)->exists())->toBeTrue();
});

test('cannot add duplicate member to cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    // Add member first
    $this->cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('openAddMemberModal')
        ->set('selectedMemberId', $member->id)
        ->set('selectedMemberRole', 'member')
        ->call('addMember')
        ->assertHasErrors(['selectedMemberId']);
});

test('admin can remove member from cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('removeMember', $member->id)
        ->assertDispatched('member-removed');

    expect($this->cluster->members()->where('member_id', $member->id)->exists())->toBeFalse();
});

test('admin can update member role in cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('updateMemberRole', $member->id, 'leader')
        ->assertDispatched('member-role-updated');

    $pivotRole = $this->cluster->members()->where('member_id', $member->id)->first()->pivot->role;
    expect($pivotRole->value)->toBe('leader');
});

test('volunteer cannot add member to cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('openAddMemberModal')
        ->assertForbidden();
});

test('volunteer cannot remove member from cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('removeMember', $member->id)
        ->assertForbidden();
});

test('available members excludes existing cluster members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $existingMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $newMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $this->cluster->members()->attach($existingMember->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);

    $availableMembers = $component->instance()->availableMembers;

    expect($availableMembers->contains('id', $existingMember->id))->toBeFalse();
    expect($availableMembers->contains('id', $newMember->id))->toBeTrue();
});

test('close add member modal resets form fields', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('openAddMemberModal')
        ->set('selectedMemberId', $member->id)
        ->set('selectedMemberRole', 'leader')
        ->call('closeAddMemberModal')
        ->assertSet('showAddMemberModal', false)
        ->assertSet('selectedMemberId', '')
        ->assertSet('selectedMemberRole', 'member');
});

// ============================================
// DELETE TESTS
// ============================================

test('admin can delete cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $clusterId = $this->cluster->id;

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertDispatched('cluster-deleted')
        ->assertRedirect(route('clusters.index', $this->branch));

    expect(Cluster::find($clusterId))->toBeNull();
});

test('manager can delete cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $clusterId = $this->cluster->id;

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertRedirect(route('clusters.index', $this->branch));

    expect(Cluster::find($clusterId))->toBeNull();
});

test('staff cannot delete cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('confirmDelete')
        ->assertForbidden();
});

test('volunteer cannot delete cluster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('confirmDelete')
        ->assertForbidden();
});

test('cancel delete modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('name is required when saving', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('edit')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('cluster type must be valid when saving', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('edit')
        ->set('cluster_type', 'invalid_type')
        ->call('save')
        ->assertHasErrors(['cluster_type']);
});

test('selected member id is required when adding member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster])
        ->call('openAddMemberModal')
        ->set('selectedMemberId', '')
        ->call('addMember')
        ->assertHasErrors(['selectedMemberId']);
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot view cluster from different branch', function (): void {
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

    Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $cluster])
        ->assertForbidden();
});

test('user cannot edit cluster from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to otherBranch but cluster belongs to $this->branch
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // The mount() will fail authorization because user doesn't have access to cluster's branch
    Livewire::test(ClusterShow::class, ['branch' => $otherBranch, 'cluster' => $this->cluster])
        ->assertForbidden();
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

test('canEdit returns true for staff and above', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    expect($component->instance()->canEdit)->toBeTrue();
});

test('canEdit returns false for volunteer', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    expect($component->instance()->canEdit)->toBeFalse();
});

test('canDelete returns true for admin and manager', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    expect($component->instance()->canDelete)->toBeTrue();
});

test('canDelete returns false for staff', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    expect($component->instance()->canDelete)->toBeFalse();
});

test('available leaders only shows active members from same branch', function (): void {
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

    $otherBranch = Branch::factory()->create();
    $otherBranchMember = Member::factory()->create([
        'primary_branch_id' => $otherBranch->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    $availableLeaders = $component->instance()->availableLeaders;

    expect($availableLeaders->contains('id', $activeMember->id))->toBeTrue();
    expect($availableLeaders->contains('id', $inactiveMember->id))->toBeFalse();
    expect($availableLeaders->contains('id', $otherBranchMember->id))->toBeFalse();
});

test('cluster members are ordered by role then name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $leader = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Alice',
        'last_name' => 'Leader',
    ]);

    $assistant = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Bob',
        'last_name' => 'Assistant',
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Carol',
        'last_name' => 'Member',
    ]);

    $this->cluster->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $this->cluster->members()->attach($assistant->id, ['role' => 'assistant', 'joined_at' => now()]);
    $this->cluster->members()->attach($leader->id, ['role' => 'leader', 'joined_at' => now()]);

    $this->actingAs($user);

    $component = Livewire::test(ClusterShow::class, ['branch' => $this->branch, 'cluster' => $this->cluster]);
    $clusterMembers = $component->instance()->clusterMembers;

    // Leader should be first, then assistant, then regular member
    expect($clusterMembers->first()->id)->toBe($leader->id);
    expect($clusterMembers->skip(1)->first()->id)->toBe($assistant->id);
    expect($clusterMembers->last()->id)->toBe($member->id);
});
