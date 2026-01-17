<?php

namespace App\Livewire\Users;

use App\Enums\BranchRole;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\InvitedToBranchNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BranchUserIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    public string $search = '';

    public bool $showInviteModal = false;

    public bool $showEditModal = false;

    public bool $showRevokeModal = false;

    // Invite form
    public string $inviteEmail = '';

    public string $inviteRole = 'staff';

    // Edit form
    public ?UserBranchAccess $editingAccess = null;

    public string $editRole = '';

    public bool $editIsPrimary = false;

    // Revoke
    public ?UserBranchAccess $revokingAccess = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [UserBranchAccess::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function users(): Collection
    {
        $query = UserBranchAccess::with('user')
            ->where('branch_id', $this->branch->id);

        // Search includes relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->whereHas('user', function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search);
    }

    #[Computed]
    public function roles(): array
    {
        return BranchRole::cases();
    }

    public function openInviteModal(): void
    {
        $this->authorize('create', [UserBranchAccess::class, $this->branch]);
        $this->resetInviteForm();
        $this->showInviteModal = true;
    }

    public function invite(): void
    {
        $this->authorize('create', [UserBranchAccess::class, $this->branch]);

        $this->validate([
            'inviteEmail' => ['required', 'email'],
            'inviteRole' => ['required', 'string', 'in:admin,manager,staff,volunteer'],
        ]);

        $user = User::where('email', $this->inviteEmail)->first();

        if (! $user) {
            $this->addError('inviteEmail', __('No user found with this email address.'));

            return;
        }

        $exists = UserBranchAccess::where('user_id', $user->id)
            ->where('branch_id', $this->branch->id)
            ->exists();

        if ($exists) {
            $this->addError('inviteEmail', __('This user already has access to this branch.'));

            return;
        }

        UserBranchAccess::create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'role' => $this->inviteRole,
            'is_primary' => false,
        ]);

        $user->notify(new InvitedToBranchNotification(
            $this->branch,
            $this->inviteRole,
            auth()->user()
        ));

        $this->showInviteModal = false;
        $this->resetInviteForm();
        $this->dispatch('user-invited');
    }

    public function edit(UserBranchAccess $access): void
    {
        $this->authorize('update', $access);

        $this->editingAccess = $access;
        $this->editRole = $access->role->value;
        $this->editIsPrimary = $access->is_primary;
        $this->showEditModal = true;
    }

    public function updateAccess(): void
    {
        $this->authorize('update', $this->editingAccess);

        $this->validate([
            'editRole' => ['required', 'string', 'in:admin,manager,staff,volunteer'],
        ]);

        // If setting as primary, remove primary from other access records for this user
        if ($this->editIsPrimary && ! $this->editingAccess->is_primary) {
            UserBranchAccess::where('user_id', $this->editingAccess->user_id)
                ->where('id', '!=', $this->editingAccess->id)
                ->update(['is_primary' => false]);
        }

        $this->editingAccess->update([
            'role' => $this->editRole,
            'is_primary' => $this->editIsPrimary,
        ]);

        $this->showEditModal = false;
        $this->editingAccess = null;
        $this->dispatch('user-updated');
    }

    public function confirmRevoke(UserBranchAccess $access): void
    {
        $this->authorize('delete', $access);

        $this->revokingAccess = $access;
        $this->showRevokeModal = true;
    }

    public function revoke(): void
    {
        $this->authorize('delete', $this->revokingAccess);

        $this->revokingAccess->delete();
        $this->showRevokeModal = false;
        $this->revokingAccess = null;
        $this->dispatch('user-revoked');
    }

    public function cancelInvite(): void
    {
        $this->showInviteModal = false;
        $this->resetInviteForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingAccess = null;
        $this->resetValidation();
    }

    public function cancelRevoke(): void
    {
        $this->showRevokeModal = false;
        $this->revokingAccess = null;
    }

    private function resetInviteForm(): void
    {
        $this->inviteEmail = '';
        $this->inviteRole = 'staff';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.users.branch-user-index');
    }
}
