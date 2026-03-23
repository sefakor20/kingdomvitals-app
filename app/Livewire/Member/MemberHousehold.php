<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Enums\HouseholdRole;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.member')]
class MemberHousehold extends Component
{
    #[Computed]
    public function member(): Member
    {
        return Member::where('user_id', auth()->id())
            ->whereNotNull('portal_activated_at')
            ->firstOrFail();
    }

    #[Computed]
    public function household(): ?Household
    {
        return $this->member->household?->load(['head', 'members']);
    }

    #[Computed]
    public function familyMembers(): Collection
    {
        if (! $this->household) {
            return collect();
        }

        return $this->household->members
            ->sortBy(fn (Member $m) => match ($m->household_role) {
                HouseholdRole::Head => 0,
                HouseholdRole::Spouse => 1,
                HouseholdRole::Child => 2,
                default => 3,
            });
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-household');
    }
}
