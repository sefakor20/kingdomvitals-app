<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\Tenant\BranchUserInvitation;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class AcceptBranchInvitation extends Component
{
    public ?BranchUserInvitation $invitation = null;

    public bool $invitationValid = false;

    public bool $userExists = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->invitation = BranchUserInvitation::with(['branch', 'invitedBy'])
            ->where('token', $token)
            ->first();

        if (! $this->invitation) {
            $this->invitationValid = false;

            return;
        }

        if ($this->invitation->isExpired()) {
            $this->invitationValid = false;

            return;
        }

        if ($this->invitation->accepted_at !== null) {
            $this->invitationValid = false;

            return;
        }

        $this->invitationValid = true;
        $this->email = $this->invitation->email;

        // Check if user already exists (registered after invitation was sent)
        $this->userExists = User::where('email', $this->email)->exists();
    }

    public function accept(): void
    {
        if (! $this->invitationValid || ! $this->invitation) {
            return;
        }

        $user = User::where('email', $this->email)->first();

        if ($user) {
            $this->acceptForExistingUser($user);
        } else {
            $this->validate();
            $this->acceptWithNewUser();
        }
    }

    private function acceptForExistingUser(User $user): void
    {
        // Check if user already has access
        $exists = UserBranchAccess::where('user_id', $user->id)
            ->where('branch_id', $this->invitation->branch_id)
            ->exists();

        if ($exists) {
            $this->invitation->markAsAccepted();
            Auth::login($user);
            $this->redirect('/dashboard', navigate: true);

            return;
        }

        // Create branch access
        UserBranchAccess::create([
            'user_id' => $user->id,
            'branch_id' => $this->invitation->branch_id,
            'role' => $this->invitation->role,
            'is_primary' => ! UserBranchAccess::where('user_id', $user->id)->exists(),
        ]);

        $this->invitation->markAsAccepted();

        Auth::login($user);

        $this->redirect('/dashboard', navigate: true);
    }

    private function acceptWithNewUser(): void
    {
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        event(new Registered($user));

        // Create branch access as primary since it's their first branch
        UserBranchAccess::create([
            'user_id' => $user->id,
            'branch_id' => $this->invitation->branch_id,
            'role' => $this->invitation->role,
            'is_primary' => true,
        ]);

        $this->invitation->markAsAccepted();

        Auth::login($user);

        $this->redirect('/dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.accept-branch-invitation');
    }
}
