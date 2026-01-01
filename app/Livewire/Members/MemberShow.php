<?php

namespace App\Livewire\Members;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MemberShow extends Component
{
    public Branch $branch;

    public Member $member;

    public function mount(Branch $branch, Member $member): void
    {
        $this->authorize('view', $member);
        $this->branch = $branch;
        $this->member = $member;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->member);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->member);
    }

    public function render()
    {
        return view('livewire.members.member-show');
    }
}
