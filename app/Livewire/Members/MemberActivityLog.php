<?php

namespace App\Livewire\Members;

use App\Models\Tenant\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MemberActivityLog extends Component
{
    public Member $member;

    public int $limit = 10;

    public function mount(Member $member): void
    {
        $this->member = $member;
    }

    #[Computed]
    public function activities(): Collection
    {
        return $this->member->activities()
            ->with('user:id,name')
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->member->activities()->count() > $this->limit;
    }

    public function loadMore(): void
    {
        $this->limit += 10;
    }

    public function render(): View
    {
        return view('livewire.members.member-activity-log');
    }
}
