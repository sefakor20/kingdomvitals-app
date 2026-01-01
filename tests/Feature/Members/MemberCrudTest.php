<?php

use App\Enums\BranchRole;
use App\Enums\MembershipStatus;
use App\Livewire\Members\MemberIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view members page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/members")
        ->assertOk()
        ->assertSeeLivewire(MemberIndex::class);
});

test('user without branch access cannot view members page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/members")
        ->assertForbidden();
});

test('unauthenticated user cannot view members page', function () {
    $this->get("/branches/{$this->branch->id}/members")
        ->assertRedirect('/login');
});

// ============================================
// VIEW MEMBERS AUTHORIZATION TESTS
// ============================================

test('admin can view members list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee($member->first_name)
        ->assertSee($member->last_name);
});

test('manager can view members list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee($member->first_name);
});

test('staff can view members list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee($member->first_name);
});

test('volunteer can view members list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee($member->first_name);
});

// ============================================
// CREATE MEMBER AUTHORIZATION TESTS
// ============================================

test('admin can create a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john.doe@example.com')
        ->set('phone', '0241234567')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('member-created');

    expect(Member::where('email', 'john.doe@example.com')->exists())->toBeTrue();
});

test('manager can create a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors();

    expect(Member::where('first_name', 'Jane')->where('last_name', 'Smith')->exists())->toBeTrue();
});

test('staff can create a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Staff')
        ->set('last_name', 'Created')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors();

    expect(Member::where('first_name', 'Staff')->exists())->toBeTrue();
});

test('volunteer cannot create a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE MEMBER AUTHORIZATION TESTS
// ============================================

test('admin can update a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'Updated')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('member-updated');

    expect($member->fresh()->first_name)->toBe('Updated');
});

test('manager can update a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'ManagerUpdated')
        ->call('update')
        ->assertHasNoErrors();

    expect($member->fresh()->first_name)->toBe('ManagerUpdated');
});

test('staff can update a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'StaffUpdated')
        ->call('update')
        ->assertHasNoErrors();

    expect($member->fresh()->first_name)->toBe('StaffUpdated');
});

test('volunteer cannot update a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertForbidden();
});

// ============================================
// DELETE MEMBER AUTHORIZATION TESTS
// ============================================

test('admin can delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('member-deleted');

    expect(Member::find($memberId))->toBeNull();
});

test('manager can delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Member::find($memberId))->toBeNull();
});

test('staff cannot delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertForbidden();
});

test('volunteer cannot delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertForbidden();
});

// ============================================
// RESTORE MEMBER TESTS
// ============================================

test('admin can see deleted members filter', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canRestore)->toBeTrue();
    $component->assertSee('Deleted');
});

test('manager can see deleted members filter', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canRestore)->toBeTrue();
    $component->assertSee('Deleted');
});

test('staff cannot see deleted members filter', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canRestore)->toBeFalse();
});

test('volunteer cannot see deleted members filter', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canRestore)->toBeFalse();
});

test('admin can restore a deleted member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    // Verify member is soft deleted
    expect(Member::find($memberId))->toBeNull();
    expect(Member::withTrashed()->find($memberId))->not->toBeNull();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('restore', $memberId)
        ->assertDispatched('member-restored');

    // Verify member is restored
    expect(Member::find($memberId))->not->toBeNull();
    expect(Member::find($memberId)->deleted_at)->toBeNull();
});

test('manager can restore a deleted member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('restore', $memberId)
        ->assertDispatched('member-restored');

    expect(Member::find($memberId))->not->toBeNull();
});

test('staff cannot restore a deleted member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('restore', $memberId)
        ->assertForbidden();

    // Member should still be deleted
    expect(Member::find($memberId))->toBeNull();
});

test('deleted members only appear in deleted view', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $activeMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'ActiveMember',
    ]);

    $deletedMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'DeletedMember',
    ]);
    $deletedMember->delete();

    $this->actingAs($user);

    // Active view should only show active member
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSet('viewFilter', 'active')
        ->assertSee('ActiveMember')
        ->assertDontSee('DeletedMember');

    // Deleted view should only show deleted member
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->assertSee('DeletedMember')
        ->assertDontSee('ActiveMember');
});

test('restored member appears in active list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'RestoredMember',
    ]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    // Restore the member
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('restore', $memberId);

    // Verify member appears in active list
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSet('viewFilter', 'active')
        ->assertSee('RestoredMember');

    // Verify member no longer appears in deleted list
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->assertDontSee('RestoredMember');
});

test('empty state shows appropriate message for deleted view', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->assertSee('No deleted members')
        ->assertSee('There are no deleted members to restore.');
});

// ============================================
// PERMANENT DELETE TESTS
// ============================================

test('admin can permanently delete a soft-deleted member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    // Verify member is soft deleted
    expect(Member::find($memberId))->toBeNull();
    expect(Member::withTrashed()->find($memberId))->not->toBeNull();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('confirmForceDelete', $memberId)
        ->assertSet('showForceDeleteModal', true)
        ->call('forceDelete')
        ->assertSet('showForceDeleteModal', false)
        ->assertDispatched('member-force-deleted');

    // Verify member is completely removed from database
    expect(Member::find($memberId))->toBeNull();
    expect(Member::withTrashed()->find($memberId))->toBeNull();
});

test('manager cannot permanently delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('confirmForceDelete', $memberId)
        ->assertForbidden();

    // Member should still exist in trashed
    expect(Member::withTrashed()->find($memberId))->not->toBeNull();
});

test('staff cannot permanently delete a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('confirmForceDelete', $memberId)
        ->assertForbidden();

    // Member should still exist in trashed
    expect(Member::withTrashed()->find($memberId))->not->toBeNull();
});

test('cancel force delete modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->call('confirmForceDelete', $memberId)
        ->assertSet('showForceDeleteModal', true)
        ->call('cancelForceDelete')
        ->assertSet('showForceDeleteModal', false)
        ->assertSet('forceDeleting', null);

    // Member should still exist in trashed
    expect(Member::withTrashed()->find($memberId))->not->toBeNull();
});

test('admin sees delete permanently button in deleted view', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member->delete();

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->assertSee('Delete Permanently');
});

test('manager does not see delete permanently button in table actions', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $memberId = $member->id;
    $member->delete();

    $this->actingAs($user);

    // Manager should not be able to trigger the force delete action
    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('viewFilter', 'deleted')
        ->assertDontSeeHtml("wire:click=\"confirmForceDelete('{$memberId}')\"");
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search members by first name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $john = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $jane = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('search', 'John')
        ->assertSee('John')
        ->assertDontSee('Jane');
});

test('can search members by last name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Mensah',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Kofi',
        'last_name' => 'Asante',
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('search', 'Mensah')
        ->assertSee('John')
        ->assertSee('Mensah')
        ->assertDontSee('Asante');
});

test('can search members by email', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Email',
        'last_name' => 'User',
        'email' => 'unique.search@example.com',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Other',
        'last_name' => 'Person',
        'email' => 'different@example.com',
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('search', 'unique.search')
        ->assertSee('Email')
        ->assertDontSee('Other');
});

test('can search members by phone', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'PhoneSearchUser',
        'last_name' => 'Found',
        'phone' => '0551234567',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'NoMatchUser',
        'last_name' => 'Hidden',
        'phone' => '0209876543',
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('search', '0551234567')
        ->assertSee('PhoneSearchUser')
        ->assertDontSee('NoMatchUser');
});

test('can filter members by status', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'ActivePerson',
        'last_name' => 'Showing',
        'status' => MembershipStatus::Active,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'InactivePerson',
        'last_name' => 'Hidden',
        'status' => MembershipStatus::Inactive,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active')
        ->assertSee('ActivePerson')
        ->assertDontSee('InactivePerson');
});

test('empty search shows all members', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('search', '');

    // Verify all 3 members are visible by checking for their names
    foreach ($members as $member) {
        $component->assertSee($member->first_name);
    }
});

// ============================================
// VALIDATION TESTS
// ============================================

test('first name is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', '')
        ->set('last_name', 'Doe')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['first_name']);
});

test('last name is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', '')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['last_name']);
});

test('email must be valid format', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'invalid-email')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['email']);
});

test('status must be valid value', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('status', 'invalid-status')
        ->call('store')
        ->assertHasErrors(['status']);
});

test('gender must be valid value', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('gender', 'invalid')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['gender']);
});

test('marital status must be valid value', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('marital_status', 'invalid')
        ->set('status', 'active')
        ->call('store')
        ->assertHasErrors(['marital_status']);
});

test('can create member with all optional fields empty', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Minimal')
        ->set('last_name', 'Member')
        ->set('status', 'active')
        ->call('store')
        ->assertHasNoErrors();

    $member = Member::where('first_name', 'Minimal')->first();
    expect($member)->not->toBeNull();
    expect($member->email)->toBeNull();
    expect($member->phone)->toBeNull();
});

test('can create member with all fields filled', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Complete')
        ->set('middle_name', 'Full')
        ->set('last_name', 'Member')
        ->set('email', 'complete@example.com')
        ->set('phone', '0241234567')
        ->set('date_of_birth', '1990-05-15')
        ->set('gender', 'male')
        ->set('marital_status', 'married')
        ->set('address', '123 Main Street')
        ->set('city', 'Accra')
        ->set('state', 'Greater Accra')
        ->set('zip', '00233')
        ->set('country', 'Ghana')
        ->set('joined_at', '2024-01-01')
        ->set('baptized_at', '2024-06-15')
        ->set('status', 'active')
        ->set('notes', 'A very active member')
        ->call('store')
        ->assertHasNoErrors();

    $member = Member::where('email', 'complete@example.com')->first();
    expect($member)->not->toBeNull();
    expect($member->first_name)->toBe('Complete');
    expect($member->middle_name)->toBe('Full');
    expect($member->city)->toBe('Accra');
});

// ============================================
// MODAL CANCEL OPERATION TESTS
// ============================================

test('cancel create modal closes modal and resets form', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Test')
        ->set('last_name', 'Name')
        ->set('email', 'test@example.com')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('first_name', '')
        ->assertSet('last_name', '')
        ->assertSet('email', '');
});

test('cancel edit modal closes modal and clears editing member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('showEditModal', true)
        ->assertSet('editingMember.id', $member->id)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingMember', null);
});

test('cancel delete modal closes modal and clears deleting member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingMember.id', $member->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingMember', null);
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update member from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Member belongs to other branch
    $member = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertForbidden();
});

test('user cannot delete member from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Member belongs to other branch
    $member = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $member)
        ->assertForbidden();
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no members exist', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee('No members found');
});

test('member table displays member information correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'last_name' => 'Member',
        'email' => 'test.member@example.com',
        'phone' => '0241112233',
        'status' => MembershipStatus::Active,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee('Test')
        ->assertSee('Member')
        ->assertSee('test.member@example.com')
        ->assertSee('0241112233')
        ->assertSee('Active');
});

test('create button is visible for users with create permission', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Member');
});

test('create button is hidden for volunteers', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    // Test that the canCreate computed property returns false for volunteers
    $component = Livewire::test(MemberIndex::class, ['branch' => $this->branch]);

    // Verify that canCreate is false
    expect($component->instance()->canCreate)->toBeFalse();

    // Verify volunteer cannot trigger create action
    $component->call('create')->assertForbidden();
});

// ============================================
// PHOTO UPLOAD TESTS
// ============================================

test('can create member with photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('photo.jpg', 200, 200);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Photo')
        ->set('last_name', 'Member')
        ->set('status', 'active')
        ->set('photo', $photo)
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $member = Member::where('first_name', 'Photo')->first();
    expect($member)->not->toBeNull();
    expect($member->photo_url)->not->toBeNull();
    expect($member->photo_url)->toContain('/storage/members/');
});

test('can update member photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('new-photo.jpg', 200, 200);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->set('photo', $photo)
        ->call('update')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->photo_url)->not->toBeNull();
    expect($member->photo_url)->toContain('/storage/members/');
});

test('can replace existing member photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create member with existing photo
    $oldPhoto = UploadedFile::fake()->image('old-photo.jpg', 200, 200);
    $oldPath = $oldPhoto->store('members/'.$this->tenant->id, 'public');
    $oldUrl = Storage::disk('public')->url($oldPath);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'photo_url' => $oldUrl,
    ]);

    $this->actingAs($user);

    $newPhoto = UploadedFile::fake()->image('new-photo.jpg', 200, 200);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->set('photo', $newPhoto)
        ->call('update')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->photo_url)->not->toBeNull();
    expect($member->photo_url)->not->toBe($oldUrl);
});

test('can remove member photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create member with existing photo
    $photo = UploadedFile::fake()->image('photo.jpg', 200, 200);
    $path = $photo->store('members/'.$this->tenant->id, 'public');
    $photoUrl = Storage::disk('public')->url($path);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'photo_url' => $photoUrl,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('existingPhotoUrl', $photoUrl)
        ->call('removePhoto')
        ->assertSet('existingPhotoUrl', null);

    $member->refresh();
    expect($member->photo_url)->toBeNull();
});

test('photo must be an image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Test')
        ->set('last_name', 'Member')
        ->set('status', 'active')
        ->set('photo', $file)
        ->call('store')
        ->assertHasErrors(['photo']);
});

test('photo must not exceed 2mb', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // Create image larger than 2MB (2048KB)
    $file = UploadedFile::fake()->image('large-photo.jpg')->size(3000);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Test')
        ->set('last_name', 'Member')
        ->set('status', 'active')
        ->set('photo', $file)
        ->call('store')
        ->assertHasErrors(['photo']);
});

test('edit modal loads existing photo url', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'photo_url' => 'http://example.com/photo.jpg',
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('edit', $member)
        ->assertSet('existingPhotoUrl', 'http://example.com/photo.jpg');
});

test('photo is stored in tenant-specific directory', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('photo.jpg', 200, 200);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'Tenant')
        ->set('last_name', 'Isolated')
        ->set('status', 'active')
        ->set('photo', $photo)
        ->call('store')
        ->assertHasNoErrors();

    $member = Member::where('first_name', 'Tenant')->first();
    expect($member->photo_url)->toContain('/storage/members/'.$this->tenant->id.'/');
});
