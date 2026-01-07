<?php

declare(strict_types=1);

use App\Enums\SuperAdminRole;
use App\Livewire\SuperAdmin\Admins\AdminIndex;
use App\Models\SuperAdmin;
use Livewire\Livewire;

it('can view admin list page as owner', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    $this->actingAs($owner, 'superadmin')
        ->get(route('superadmin.admins.index'))
        ->assertOk()
        ->assertSee('Super Admins');
});

it('can view admin list page as regular admin but cannot manage', function () {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(AdminIndex::class)
        ->assertSet('canManage', false)
        ->assertDontSee('Add Admin');
});

it('shows all admins in the list', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin1 = SuperAdmin::factory()->create(['name' => 'John Doe']);
    $admin2 = SuperAdmin::factory()->create(['name' => 'Jane Smith']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith');
});

it('can search admins by name', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    SuperAdmin::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

it('can search admins by email', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    SuperAdmin::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('search', 'jane@')
        ->assertSee('Jane Smith')
        ->assertDontSee('John Doe');
});

it('can filter admins by role', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->create(['name' => 'Support User', 'role' => SuperAdminRole::Support]);
    SuperAdmin::factory()->create(['name' => 'Admin User', 'role' => SuperAdminRole::Admin]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('role', 'support')
        ->assertSee('Support User')
        ->assertDontSee('Admin User');
});

it('can filter admins by status', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->create(['name' => 'Active User', 'is_active' => true]);
    SuperAdmin::factory()->create(['name' => 'Inactive User', 'is_active' => false]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('status', 'inactive')
        ->assertSee('Inactive User')
        ->assertDontSee('Active User');
});

it('can create a new admin as owner', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('showCreateModal', true)
        ->set('createName', 'New Admin')
        ->set('createEmail', 'newadmin@example.com')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->set('createRole', 'admin')
        ->set('createIsActive', true)
        ->call('createAdmin')
        ->assertSet('showCreateModal', false)
        ->assertDispatched('admin-created');

    $this->assertDatabaseHas('super_admins', [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'role' => 'admin',
        'is_active' => true,
    ]);
});

it('validates required fields when creating admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('showCreateModal', true)
        ->call('createAdmin')
        ->assertHasErrors(['createName', 'createEmail', 'createPassword']);
});

it('validates unique email when creating admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->create(['email' => 'existing@example.com']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('showCreateModal', true)
        ->set('createName', 'New Admin')
        ->set('createEmail', 'existing@example.com')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->call('createAdmin')
        ->assertHasErrors(['createEmail']);
});

it('regular admin cannot create new admins', function () {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(AdminIndex::class)
        ->set('showCreateModal', true)
        ->set('createName', 'New Admin')
        ->set('createEmail', 'newadmin@example.com')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->call('createAdmin')
        ->assertForbidden();
});

it('can open edit modal for an admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create(['name' => 'Edit Me']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openEditModal', $admin->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editName', 'Edit Me')
        ->assertSet('editAdminId', $admin->id);
});

it('can update an admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openEditModal', $admin->id)
        ->set('editName', 'New Name')
        ->set('editEmail', 'newemail@example.com')
        ->call('updateAdmin')
        ->assertSet('showEditModal', false)
        ->assertDispatched('admin-updated');

    $this->assertDatabaseHas('super_admins', [
        'id' => $admin->id,
        'name' => 'New Name',
        'email' => 'newemail@example.com',
    ]);
});

it('cannot change own role', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openEditModal', $owner->id)
        ->set('editRole', 'admin')
        ->call('updateAdmin')
        ->assertHasErrors(['editRole']);

    // Role should remain unchanged
    $owner->refresh();
    expect($owner->role)->toBe(SuperAdminRole::Owner);
});

it('cannot deactivate own account', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openEditModal', $owner->id)
        ->set('editIsActive', false)
        ->call('updateAdmin')
        ->assertHasErrors(['editIsActive']);

    // Should remain active
    $owner->refresh();
    expect($owner->is_active)->toBeTrue();
});

it('can reset password for an admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create();
    $oldPassword = $admin->password;

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openResetPasswordModal', $admin->id)
        ->assertSet('showResetPasswordModal', true)
        ->set('newPassword', 'NewPassword123!')
        ->set('newPasswordConfirmation', 'NewPassword123!')
        ->call('resetPassword')
        ->assertSet('showResetPasswordModal', false)
        ->assertDispatched('password-reset');

    $admin->refresh();
    expect($admin->password)->not->toBe($oldPassword);
});

it('validates password confirmation when resetting', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openResetPasswordModal', $admin->id)
        ->set('newPassword', 'NewPassword123!')
        ->set('newPasswordConfirmation', 'DifferentPassword!')
        ->call('resetPassword')
        ->assertHasErrors(['newPassword']);
});

it('can delete an admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create(['name' => 'Delete Me']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('confirmDelete', $admin->id)
        ->assertSet('showDeleteModal', true)
        ->call('deleteAdmin')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('admin-deleted');

    $this->assertDatabaseMissing('super_admins', ['id' => $admin->id]);
});

it('cannot delete self', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('confirmDelete', $owner->id)
        ->call('deleteAdmin')
        ->assertHasErrors(['delete']);

    $this->assertDatabaseHas('super_admins', ['id' => $owner->id]);
});

it('cannot delete the last owner', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    // Ensure there's only one owner
    expect(SuperAdmin::where('role', SuperAdminRole::Owner)->count())->toBe(1);

    // Create a second owner to do the deletion
    $secondOwner = SuperAdmin::factory()->owner()->create();

    // Now secondOwner tries to delete the first owner
    Livewire::actingAs($secondOwner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('confirmDelete', $owner->id)
        ->call('deleteAdmin')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('admin-deleted');

    // First owner should be deleted since there's still secondOwner
    $this->assertDatabaseMissing('super_admins', ['id' => $owner->id]);
});

it('prevents deleting the very last owner', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    // Create a regular admin to try deletion
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    // Try to delete as a regular admin - should be forbidden
    Livewire::actingAs($admin, 'superadmin')
        ->test(AdminIndex::class)
        ->call('confirmDelete', $owner->id)
        ->assertForbidden();
});

it('shows 2FA status badge for admins', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $adminWith2FA = SuperAdmin::factory()->create([
        'name' => 'Has 2FA',
        'two_factor_confirmed_at' => now(),
    ]);
    $adminWithout2FA = SuperAdmin::factory()->create([
        'name' => 'No 2FA',
        'two_factor_confirmed_at' => null,
    ]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->assertSee('Has 2FA')
        ->assertSee('No 2FA')
        ->assertSee('Enabled')
        ->assertSee('Disabled');
});

it('logs activity when creating admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->set('showCreateModal', true)
        ->set('createName', 'New Admin')
        ->set('createEmail', 'newadmin@example.com')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->set('createRole', 'admin')
        ->call('createAdmin');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'admin_created',
    ]);
});

it('logs activity when updating admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openEditModal', $admin->id)
        ->set('editName', 'Updated Name')
        ->call('updateAdmin');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'admin_updated',
    ]);
});

it('logs activity when deleting admin', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('confirmDelete', $admin->id)
        ->call('deleteAdmin');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'admin_deleted',
    ]);
});

it('logs activity when resetting password', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->call('openResetPasswordModal', $admin->id)
        ->set('newPassword', 'NewPassword123!')
        ->set('newPasswordConfirmation', 'NewPassword123!')
        ->call('resetPassword');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $owner->id,
        'action' => 'admin_password_reset',
    ]);
});

it('shows "You" badge next to current user', function () {
    $owner = SuperAdmin::factory()->owner()->create(['name' => 'Current Owner']);

    Livewire::actingAs($owner, 'superadmin')
        ->test(AdminIndex::class)
        ->assertSee('Current Owner')
        ->assertSee('You');
});

it('sidebar shows admins link only for owners', function () {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    // Owner should see Admins link
    $this->actingAs($owner, 'superadmin')
        ->get(route('superadmin.dashboard'))
        ->assertSee('Admins');

    // Regular admin should not see Admins link
    $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.dashboard'))
        ->assertDontSee('href="'.route('superadmin.admins.index').'"');
});
