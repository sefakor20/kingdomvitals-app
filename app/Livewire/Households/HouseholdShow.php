<?php

declare(strict_types=1);

namespace App\Livewire\Households;

use App\Enums\HouseholdRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HouseholdShow extends Component
{
    public Branch $branch;

    public Household $household;

    // Add member modal
    public bool $showAddMemberModal = false;

    public string $memberSearch = '';

    public ?string $selectedMemberId = null;

    public string $selectedRole = 'other';

    // Edit role modal
    public bool $showEditRoleModal = false;

    public ?Member $editingMember = null;

    public string $editingRole = '';

    // Remove member modal
    public bool $showRemoveMemberModal = false;

    public ?Member $removingMember = null;

    public function mount(Branch $branch, Household $household): void
    {
        $this->authorize('view', $household);
        $this->branch = $branch;
        $this->household = $household;
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('household_id', $this->household->id)
            ->orderByRaw("CASE WHEN household_role = 'head' THEN 1 WHEN household_role = 'spouse' THEN 2 WHEN household_role = 'child' THEN 3 ELSE 4 END")
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function availableMembers(): Collection
    {
        $query = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNull('household_id');

        if ($this->memberSearch !== '' && $this->memberSearch !== '0') {
            $search = $this->memberSearch;
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function roles(): array
    {
        return HouseholdRole::cases();
    }

    #[Computed]
    public function canManageMembers(): bool
    {
        return auth()->user()->can('update', $this->household);
    }

    public function openAddMemberModal(): void
    {
        $this->authorize('update', $this->household);
        $this->memberSearch = '';
        $this->selectedMemberId = null;
        $this->selectedRole = 'other';
        $this->showAddMemberModal = true;
    }

    public function selectMember(string $memberId): void
    {
        $this->selectedMemberId = $memberId;
    }

    public function addMember(): void
    {
        $this->authorize('update', $this->household);

        if (! $this->selectedMemberId) {
            return;
        }

        $member = Member::where('id', $this->selectedMemberId)
            ->where('primary_branch_id', $this->branch->id)
            ->whereNull('household_id')
            ->first();

        if (! $member) {
            return;
        }

        $member->update([
            'household_id' => $this->household->id,
            'household_role' => $this->selectedRole,
        ]);

        // If adding as head, update household
        if ($this->selectedRole === 'head') {
            // Remove old head's role
            if ($this->household->head_id) {
                Member::where('id', $this->household->head_id)->update([
                    'household_role' => 'other',
                ]);
            }
            $this->household->update(['head_id' => $member->id]);
        }

        $this->showAddMemberModal = false;
        $this->selectedMemberId = null;
        $this->memberSearch = '';
        unset($this->members);
        unset($this->availableMembers);
    }

    public function cancelAddMember(): void
    {
        $this->showAddMemberModal = false;
        $this->selectedMemberId = null;
        $this->memberSearch = '';
    }

    public function openEditRoleModal(Member $member): void
    {
        $this->authorize('update', $this->household);
        $this->editingMember = $member;
        $this->editingRole = $member->household_role?->value ?? 'other';
        $this->showEditRoleModal = true;
    }

    public function updateRole(): void
    {
        $this->authorize('update', $this->household);

        if (!$this->editingMember instanceof \App\Models\Tenant\Member) {
            return;
        }

        $oldRole = $this->editingMember->household_role?->value;

        $this->editingMember->update([
            'household_role' => $this->editingRole,
        ]);

        // If changing to head, update household
        if ($this->editingRole === 'head' && $oldRole !== 'head') {
            // Remove old head's role
            if ($this->household->head_id && $this->household->head_id !== $this->editingMember->id) {
                Member::where('id', $this->household->head_id)->update([
                    'household_role' => 'other',
                ]);
            }
            $this->household->update(['head_id' => $this->editingMember->id]);
        } elseif ($oldRole === 'head' && $this->editingRole !== 'head') {
            // If removing head role, clear household head
            $this->household->update(['head_id' => null]);
        }

        $this->showEditRoleModal = false;
        $this->editingMember = null;
        unset($this->members);
    }

    public function cancelEditRole(): void
    {
        $this->showEditRoleModal = false;
        $this->editingMember = null;
    }

    public function confirmRemoveMember(Member $member): void
    {
        $this->authorize('update', $this->household);
        $this->removingMember = $member;
        $this->showRemoveMemberModal = true;
    }

    public function removeMember(): void
    {
        $this->authorize('update', $this->household);

        if (!$this->removingMember instanceof \App\Models\Tenant\Member) {
            return;
        }

        // If removing head, clear household head
        if ($this->household->head_id === $this->removingMember->id) {
            $this->household->update(['head_id' => null]);
        }

        $this->removingMember->update([
            'household_id' => null,
            'household_role' => null,
        ]);

        $this->showRemoveMemberModal = false;
        $this->removingMember = null;
        unset($this->members);
    }

    public function cancelRemoveMember(): void
    {
        $this->showRemoveMemberModal = false;
        $this->removingMember = null;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.households.household-show');
    }
}
