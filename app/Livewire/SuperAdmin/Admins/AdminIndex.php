<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Admins;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    // Create modal
    public bool $showCreateModal = false;

    public string $createName = '';

    public string $createEmail = '';

    public string $createPassword = '';

    public string $createPasswordConfirmation = '';

    public string $createRole = 'admin';

    public bool $createIsActive = true;

    // Edit modal
    public bool $showEditModal = false;

    public ?string $editAdminId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editRole = '';

    public bool $editIsActive = true;

    // Reset password modal
    public bool $showResetPasswordModal = false;

    public ?string $resetPasswordAdminId = null;

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // Delete modal
    public bool $showDeleteModal = false;

    public ?string $deleteAdminId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createEmail = '';
        $this->createPassword = '';
        $this->createPasswordConfirmation = '';
        $this->createRole = 'admin';
        $this->createIsActive = true;
        $this->resetValidation();
    }

    public function createAdmin(): void
    {
        $this->ensureCanManageAdmins();

        $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'string', 'email', 'max:255', 'unique:super_admins,email'],
            'createPassword' => ['required', 'string', Password::defaults(), 'confirmed:createPasswordConfirmation'],
            'createRole' => ['required', 'string', 'in:owner,admin,support'],
        ]);

        $admin = SuperAdmin::create([
            'name' => $this->createName,
            'email' => $this->createEmail,
            'password' => Hash::make($this->createPassword),
            'role' => SuperAdminRole::from($this->createRole),
            'is_active' => $this->createIsActive,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'admin_created',
            description: "Created super admin: {$admin->name}",
            metadata: [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'admin_role' => $admin->role->value,
            ],
        );

        $this->showCreateModal = false;
        $this->resetCreateForm();
        $this->dispatch('admin-created');
    }

    public function openEditModal(string $adminId): void
    {
        $this->ensureCanManageAdmins();

        $admin = SuperAdmin::findOrFail($adminId);
        $this->editAdminId = $admin->id;
        $this->editName = $admin->name;
        $this->editEmail = $admin->email;
        $this->editRole = $admin->role->value;
        $this->editIsActive = $admin->is_active;
        $this->showEditModal = true;
    }

    public function updateAdmin(): void
    {
        $this->ensureCanManageAdmins();

        $admin = SuperAdmin::findOrFail($this->editAdminId);
        $currentUser = Auth::guard('superadmin')->user();

        // Cannot change own role or deactivate self
        $isSelf = $admin->id === $currentUser->id;

        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'string', 'email', 'max:255', 'unique:super_admins,email,'.$admin->id],
            'editRole' => ['required', 'string', 'in:owner,admin,support'],
        ]);

        if ($isSelf && $this->editRole !== $admin->role->value) {
            $this->addError('editRole', 'You cannot change your own role.');

            return;
        }

        if ($isSelf && ! $this->editIsActive) {
            $this->addError('editIsActive', 'You cannot deactivate your own account.');

            return;
        }

        $oldValues = $admin->only(['name', 'email', 'role', 'is_active']);

        $admin->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'role' => SuperAdminRole::from($this->editRole),
            'is_active' => $this->editIsActive,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: $currentUser,
            action: 'admin_updated',
            description: "Updated super admin: {$admin->name}",
            metadata: [
                'admin_id' => $admin->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $this->editName,
                    'email' => $this->editEmail,
                    'role' => $this->editRole,
                    'is_active' => $this->editIsActive,
                ],
            ],
        );

        $this->showEditModal = false;
        $this->editAdminId = null;
        $this->dispatch('admin-updated');
    }

    public function openResetPasswordModal(string $adminId): void
    {
        $this->ensureCanManageAdmins();

        $this->resetPasswordAdminId = $adminId;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->showResetPasswordModal = true;
    }

    public function resetPassword(): void
    {
        $this->ensureCanManageAdmins();

        $this->validate([
            'newPassword' => ['required', 'string', Password::defaults(), 'confirmed:newPasswordConfirmation'],
        ]);

        $admin = SuperAdmin::findOrFail($this->resetPasswordAdminId);

        $admin->update([
            'password' => Hash::make($this->newPassword),
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'admin_password_reset',
            description: "Reset password for super admin: {$admin->name}",
            metadata: ['admin_id' => $admin->id],
        );

        $this->showResetPasswordModal = false;
        $this->resetPasswordAdminId = null;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->dispatch('password-reset');
    }

    public function confirmDelete(string $adminId): void
    {
        $this->ensureCanManageAdmins();

        $this->deleteAdminId = $adminId;
        $this->showDeleteModal = true;
    }

    public function deleteAdmin(): void
    {
        $this->ensureCanManageAdmins();

        $admin = SuperAdmin::findOrFail($this->deleteAdminId);
        $currentUser = Auth::guard('superadmin')->user();

        // Cannot delete self
        if ($admin->id === $currentUser->id) {
            $this->addError('delete', 'You cannot delete your own account.');
            $this->showDeleteModal = false;

            return;
        }

        // Cannot delete last owner
        if ($admin->role === SuperAdminRole::Owner) {
            $ownerCount = SuperAdmin::where('role', SuperAdminRole::Owner)->count();
            if ($ownerCount <= 1) {
                $this->addError('delete', 'Cannot delete the last owner account.');
                $this->showDeleteModal = false;

                return;
            }
        }

        $adminName = $admin->name;
        $adminId = $admin->id;

        $admin->delete();

        SuperAdminActivityLog::log(
            superAdmin: $currentUser,
            action: 'admin_deleted',
            description: "Deleted super admin: {$adminName}",
            metadata: ['admin_id' => $adminId],
        );

        $this->showDeleteModal = false;
        $this->deleteAdminId = null;
        $this->dispatch('admin-deleted');
    }

    private function ensureCanManageAdmins(): void
    {
        $currentUser = Auth::guard('superadmin')->user();

        if (! $currentUser->role->canManageSuperAdmins()) {
            abort(403, 'You do not have permission to manage super admins.');
        }
    }

    public function render(): View
    {
        $currentUser = Auth::guard('superadmin')->user();
        $canManage = $currentUser->role->canManageSuperAdmins();

        $query = SuperAdmin::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->role, function ($query) {
                $query->where('role', $this->role);
            })
            ->when($this->status !== '', function ($query) {
                $query->where('is_active', $this->status === 'active');
            })
            ->latest();

        return view('livewire.super-admin.admins.admin-index', [
            'admins' => $query->paginate(15),
            'roles' => SuperAdminRole::cases(),
            'canManage' => $canManage,
            'currentUserId' => $currentUser->id,
        ])->layout('components.layouts.superadmin.app');
    }
}
