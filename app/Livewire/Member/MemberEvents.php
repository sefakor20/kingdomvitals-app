<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.member')]
class MemberEvents extends Component
{
    #[Computed]
    public function member(): Member
    {
        return app('currentMember');
    }

    #[Computed]
    public function upcomingRegistrations(): Collection
    {
        return EventRegistration::query()
            ->with('event')
            ->where('member_id', $this->member->id)
            ->whereHas('event', function ($query) {
                $query->where('starts_at', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function pastRegistrations(): Collection
    {
        return EventRegistration::query()
            ->with('event')
            ->where('member_id', $this->member->id)
            ->whereHas('event', function ($query) {
                $query->where('starts_at', '<', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-events');
    }
}
