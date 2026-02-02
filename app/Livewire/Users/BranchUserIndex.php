<?php

namespace App\Livewire\Users;

use App\Enums\BranchRole;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchUserInvitation;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\BranchUserInvitationNotification;
use App\Notifications\InvitedToBranchNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BranchUserIndex extends Component
{
    use HasFilterableQuery;

    #[Locked]
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

    #[Computed]
    public function pendingInvitations(): Collection
    {
        return BranchUserInvitation::where('branch_id', $this->branch->id)
            ->pending()
            ->with('invitedBy')
            ->orderBy('created_at', 'desc')
            ->get();
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

        if ($user) {
            $this->addExistingUser($user);
        } else {
            $this->createPendingInvitation();
        }
    }

    private function addExistingUser(User $user): void
    {
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
        unset($this->pendingInvitations);
        $this->dispatch('user-invited');
    }

    private function createPendingInvitation(): void
    {
        $existing = BranchUserInvitation::where('branch_id', $this->branch->id)
            ->where('email', $this->inviteEmail)
            ->pending()
            ->first();

        if ($existing) {
            $acceptUrl = tenant_route(tenant()->domains->first()?->domain ?? '', 'invitations.accept', ['token' => $existing->token]);

            Notification::route('mail', $this->inviteEmail)
                ->notify(new BranchUserInvitationNotification(
                    $existing,
                    $acceptUrl,
                    tenant()?->getLogoUrl('medium'),
                    tenant()?->name
                ));

            $this->showInviteModal = false;
            $this->resetInviteForm();
            $this->dispatch('notification', [
                'type' => 'success',
                'message' => __('Invitation resent to :email.', ['email' => $existing->email]),
            ]);

            return;
        }

        $invitation = BranchUserInvitation::create([
            'branch_id' => $this->branch->id,
            'email' => $this->inviteEmail,
            'role' => $this->inviteRole,
            'token' => BranchUserInvitation::generateToken(),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        $acceptUrl = tenant_route(tenant()->domains->first()?->domain ?? '', 'invitations.accept', ['token' => $invitation->token]);

        Notification::route('mail', $this->inviteEmail)
            ->notify(new BranchUserInvitationNotification(
                $invitation,
                $acceptUrl,
                tenant()?->getLogoUrl('medium'),
                tenant()?->name
            ));

        $this->showInviteModal = false;
        $this->resetInviteForm();
        unset($this->pendingInvitations);
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('Invitation sent to :email.', ['email' => $invitation->email]),
        ]);
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

    public function resendInvitation(string $invitationId): void
    {
        $this->authorize('create', [UserBranchAccess::class, $this->branch]);

        $invitation = BranchUserInvitation::where('branch_id', $this->branch->id)
            ->where('id', $invitationId)
            ->pending()
            ->firstOrFail();

        $acceptUrl = tenant_route(tenant()->domains->first()?->domain ?? '', 'invitations.accept', ['token' => $invitation->token]);

        Notification::route('mail', $invitation->email)
            ->notify(new BranchUserInvitationNotification(
                $invitation,
                $acceptUrl,
                tenant()?->getLogoUrl('medium'),
                tenant()?->name
            ));

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('Invitation resent to :email.', ['email' => $invitation->email]),
        ]);
    }

    public function cancelPendingInvitation(string $invitationId): void
    {
        $this->authorize('create', [UserBranchAccess::class, $this->branch]);

        $invitation = BranchUserInvitation::where('branch_id', $this->branch->id)
            ->where('id', $invitationId)
            ->pending()
            ->firstOrFail();

        $invitation->delete();

        unset($this->pendingInvitations);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('Invitation cancelled.'),
        ]);
    }

    public function sendPasswordResetLink(UserBranchAccess $access): void
    {
        $this->authorize('update', $access);

        $user = $access->user;

        Password::broker('users')->sendResetLink(['email' => $user->email]);

        $this->dispatch('password-reset-sent');
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
