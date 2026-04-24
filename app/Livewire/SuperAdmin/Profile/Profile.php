<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Profile;

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.superadmin.app')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $admin = Auth::guard('superadmin')->user();

        $this->name = $admin->name;
        $this->email = $admin->email;
    }

    public function updateProfileInformation(): void
    {
        $admin = Auth::guard('superadmin')->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(SuperAdmin::class)->ignore($admin->id),
            ],
        ]);

        $admin->fill($validated);

        if ($admin->isDirty('email')) {
            $admin->email_verified_at = null;
        }

        $admin->save();

        $this->dispatch('profile-updated', name: $admin->name);
    }
}
