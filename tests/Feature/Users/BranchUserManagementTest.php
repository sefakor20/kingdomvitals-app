<?php

use App\Enums\BranchRole;
use App\Livewire\Users\BranchUserIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
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
// AUTHORIZATION TESTS
// ============================================

test('admin can view branch users page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get(route('branches.users.index', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(BranchUserIndex::class);
});

test('manager cannot view branch users page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user)
        ->get(route('branches.users.index', $this->branch))
        ->assertForbidden();
});

test('staff cannot view branch users page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('branches.users.index', $this->branch))
        ->assertForbidden();
});

test('volunteer cannot view branch users page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user)
        ->get(route('branches.users.index', $this->branch))
        ->assertForbidden();
});

// ============================================
// INVITE USER TESTS
// ============================================

test('admin can invite existing user to branch', function () {
    $admin = User::factory()->create();
    $userToInvite = User::factory()->create(['email' => 'invite@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->assertSet('showInviteModal', true)
        ->set('inviteEmail', 'invite@example.com')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('showInviteModal', false)
        ->assertDispatched('user-invited');

    $access = UserBranchAccess::where('user_id', $userToInvite->id)
        ->where('branch_id', $this->branch->id)
        ->first();

    expect($access)->not->toBeNull();
    expect($access->role)->toBe(BranchRole::Staff);
});

test('cannot invite user who does not exist', function () {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'nonexistent@example.com')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasErrors(['inviteEmail']);
});

test('cannot invite user who already has access', function () {
    $admin = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $existingUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'existing@example.com')
        ->set('inviteRole', 'manager')
        ->call('invite')
        ->assertHasErrors(['inviteEmail']);
});

test('invite requires valid email format', function () {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'invalid-email')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasErrors(['inviteEmail']);
});

test('invite requires valid role', function () {
    $admin = User::factory()->create();
    $userToInvite = User::factory()->create(['email' => 'invite@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'invite@example.com')
        ->set('inviteRole', 'invalid-role')
        ->call('invite')
        ->assertHasErrors(['inviteRole']);
});

// ============================================
// EDIT USER ACCESS TESTS
// ============================================

test('admin can edit user role', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('edit', $targetAccess)
        ->assertSet('showEditModal', true)
        ->assertSet('editRole', 'staff')
        ->set('editRole', 'manager')
        ->call('updateAccess')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('user-updated');

    expect($targetAccess->fresh()->role)->toBe(BranchRole::Manager);
});

test('admin can set user primary branch', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
        'is_primary' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('edit', $targetAccess)
        ->set('editIsPrimary', true)
        ->call('updateAccess')
        ->assertHasNoErrors();

    expect($targetAccess->fresh()->is_primary)->toBeTrue();
});

test('setting primary clears other primary flags for same user', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $primaryAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Staff,
        'is_primary' => true,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
        'is_primary' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('edit', $targetAccess)
        ->set('editIsPrimary', true)
        ->call('updateAccess')
        ->assertHasNoErrors();

    expect($targetAccess->fresh()->is_primary)->toBeTrue();
    expect($primaryAccess->fresh()->is_primary)->toBeFalse();
});

test('admin cannot edit own access', function () {
    $admin = User::factory()->create();

    $adminAccess = UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('edit', $adminAccess)
        ->assertForbidden();
});

// ============================================
// REVOKE ACCESS TESTS
// ============================================

test('admin can revoke user access', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('confirmRevoke', $targetAccess)
        ->assertSet('showRevokeModal', true)
        ->call('revoke')
        ->assertSet('showRevokeModal', false)
        ->assertDispatched('user-revoked');

    expect(UserBranchAccess::find($targetAccess->id))->toBeNull();
});

test('admin cannot revoke own access', function () {
    $admin = User::factory()->create();

    $adminAccess = UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('confirmRevoke', $adminAccess)
        ->assertForbidden();
});

// ============================================
// SEARCH TESTS
// ============================================

test('can search users by name', function () {
    $admin = User::factory()->create();
    $user1 = User::factory()->create(['name' => 'John Doe']);
    $user2 = User::factory()->create(['name' => 'Jane Smith']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user1->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user2->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->assertSee('John Doe')
        ->assertSee('Jane Smith')
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('can search users by email', function () {
    $admin = User::factory()->create();
    $user1 = User::factory()->create(['email' => 'john@example.com']);
    $user2 = User::factory()->create(['email' => 'jane@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user1->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user2->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->set('search', 'john@')
        ->assertSee('john@example.com')
        ->assertDontSee('jane@example.com');
});

// ============================================
// MODAL CANCEL TESTS
// ============================================

test('cancel invite modal closes and resets form', function () {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->assertSet('showInviteModal', true)
        ->set('inviteEmail', 'test@example.com')
        ->set('inviteRole', 'manager')
        ->call('cancelInvite')
        ->assertSet('showInviteModal', false)
        ->assertSet('inviteEmail', '')
        ->assertSet('inviteRole', 'staff');
});

test('cancel edit modal closes and clears editing access', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('edit', $targetAccess)
        ->assertSet('showEditModal', true)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingAccess', null);
});

test('cancel revoke modal closes and clears revoking access', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $targetAccess = UserBranchAccess::factory()->create([
        'user_id' => $targetUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('confirmRevoke', $targetAccess)
        ->assertSet('showRevokeModal', true)
        ->call('cancelRevoke')
        ->assertSet('showRevokeModal', false)
        ->assertSet('revokingAccess', null);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('users list shows user information correctly', function () {
    $admin = User::factory()->create();
    $staff = User::factory()->create(['name' => 'Staff User', 'email' => 'staff@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
        'is_primary' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->assertSee('Staff User')
        ->assertSee('staff@example.com')
        ->assertSee('Staff')
        ->assertSee('Primary');
});

test('current user sees (You) label and cannot edit/revoke self', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->assertSee('Admin User')
        ->assertSee('(You)');
});

test('empty state shows when no users match search', function () {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->set('search', 'nonexistent-user-xyz')
        ->assertSee('No users found')
        ->assertSee('Try adjusting your search criteria');
});

test('all roles are available in invite form', function () {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->assertSee('Admin')
        ->assertSee('Manager')
        ->assertSee('Staff')
        ->assertSee('Volunteer');
});
