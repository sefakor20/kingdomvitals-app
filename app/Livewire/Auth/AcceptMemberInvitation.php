<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\Tenant\MemberInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class AcceptMemberInvitation extends Component
{
    public ?MemberInvitation $invitation = null;

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
        $this->invitation = MemberInvitation::with(['branch', 'member', 'invitedBy'])
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

        if ($this->invitation->isAccepted()) {
            $this->invitationValid = false;

            return;
        }

        $this->invitationValid = true;
        $this->email = $this->invitation->email;

        // Pre-fill name from member record
        if ($this->invitation->member) {
            $this->name = $this->invitation->member->fullName();
        }

        // Check if user already exists
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
        // Mark email as verified since they clicked the invitation link
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Activate portal access for the member
        $this->invitation->member->activatePortal($user);

        $this->invitation->markAsAccepted();

        Auth::login($user);

        $this->redirect(route('member.dashboard'), navigate: true);
    }

    private function acceptWithNewUser(): void
    {
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);

        // Activate portal access for the member
        $this->invitation->member->activatePortal($user);

        $this->invitation->markAsAccepted();

        Auth::login($user);

        $this->redirect(route('member.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.accept-member-invitation');
    }
}
