<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Models\Tenant\Member;
use Flux\Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.member')]
class MemberContactInfo extends Component
{
    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('nullable|string|max:20')]
    public string $zip = '';

    #[Validate('nullable|string|max:100')]
    public string $profession = '';

    public function mount(): void
    {
        $member = $this->member;

        $this->phone = $member->phone ?? '';
        $this->address = $member->address ?? '';
        $this->city = $member->city ?? '';
        $this->state = $member->state ?? '';
        $this->zip = $member->zip ?? '';
        $this->profession = $member->profession ?? '';
    }

    #[Computed]
    public function member(): Member
    {
        return Member::where('user_id', auth()->id())
            ->whereNotNull('portal_activated_at')
            ->firstOrFail();
    }

    public function save(): void
    {
        $this->validate();

        $this->member->update([
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'zip' => $this->zip ?: null,
            'profession' => $this->profession ?: null,
        ]);

        Flux::toast(__('Contact information updated successfully.'));
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-contact-info');
    }
}
