<?php

namespace App\Livewire\Clusters;

use App\Enums\ClusterRole;
use App\Enums\ClusterType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ClusterShow extends Component
{
    public Branch $branch;

    public Cluster $cluster;

    public bool $editing = false;

    // Form fields
    public string $name = '';

    public string $cluster_type = '';

    public string $description = '';

    public ?string $leader_id = null;

    public ?string $assistant_leader_id = null;

    public string $meeting_day = '';

    public ?string $meeting_time = null;

    public string $meeting_location = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    public string $notes = '';

    // Member management properties
    public bool $showAddMemberModal = false;

    public string $selectedMemberId = '';

    public string $selectedMemberRole = 'member';

    public ?string $memberJoinedAt = null;

    // Delete modal
    public bool $showDeleteModal = false;

    public function mount(Branch $branch, Cluster $cluster): void
    {
        $this->authorize('view', $cluster);
        $this->branch = $branch;
        $this->cluster = $cluster;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->cluster);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->cluster);
    }

    #[Computed]
    public function clusterTypes(): array
    {
        return ClusterType::cases();
    }

    #[Computed]
    public function clusterRoles(): array
    {
        return ClusterRole::cases();
    }

    #[Computed]
    public function availableLeaders(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function clusterMembers(): Collection
    {
        return $this->cluster->members()
            ->orderByRaw("CASE WHEN cluster_member.role = 'leader' THEN 1 WHEN cluster_member.role = 'assistant' THEN 2 ELSE 3 END")
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function availableMembers(): Collection
    {
        $existingMemberIds = $this->cluster->members()->pluck('members.id')->toArray();

        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'cluster_type' => ['required', Rule::enum(ClusterType::class)],
            'description' => ['nullable', 'string'],
            'leader_id' => ['nullable', 'uuid', 'exists:members,id'],
            'assistant_leader_id' => ['nullable', 'uuid', 'exists:members,id'],
            'meeting_day' => ['nullable', 'string', 'max:20'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'meeting_location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->cluster);

        $this->fill([
            'name' => $this->cluster->name,
            'cluster_type' => $this->cluster->cluster_type->value,
            'description' => $this->cluster->description ?? '',
            'leader_id' => $this->cluster->leader_id,
            'assistant_leader_id' => $this->cluster->assistant_leader_id,
            'meeting_day' => $this->cluster->meeting_day ?? '',
            'meeting_time' => $this->cluster->meeting_time,
            'meeting_location' => $this->cluster->meeting_location ?? '',
            'capacity' => $this->cluster->capacity,
            'is_active' => $this->cluster->is_active,
            'notes' => $this->cluster->notes ?? '',
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->cluster);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'description', 'leader_id', 'assistant_leader_id',
            'meeting_day', 'meeting_time', 'meeting_location',
            'capacity', 'notes',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->cluster->update($validated);
        $this->cluster->refresh();

        $this->editing = false;
        $this->dispatch('cluster-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function openAddMemberModal(): void
    {
        $this->authorize('update', $this->cluster);

        $this->reset(['selectedMemberId', 'selectedMemberRole', 'memberJoinedAt']);
        $this->selectedMemberRole = ClusterRole::Member->value;
        $this->memberJoinedAt = now()->format('Y-m-d');
        $this->showAddMemberModal = true;
    }

    public function closeAddMemberModal(): void
    {
        $this->showAddMemberModal = false;
        $this->reset(['selectedMemberId', 'selectedMemberRole', 'memberJoinedAt']);
    }

    public function addMember(): void
    {
        $this->authorize('update', $this->cluster);

        $this->validate([
            'selectedMemberId' => ['required', 'uuid', 'exists:members,id'],
            'selectedMemberRole' => ['required', 'string', 'in:leader,assistant,member'],
            'memberJoinedAt' => ['nullable', 'date'],
        ]);

        // Verify member belongs to same branch and is active
        $member = Member::where('id', $this->selectedMemberId)
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->first();

        if (! $member) {
            $this->addError('selectedMemberId', __('Invalid member selected.'));

            return;
        }

        // Check if already a member
        if ($this->cluster->members()->where('member_id', $member->id)->exists()) {
            $this->addError('selectedMemberId', __('Member is already in this cluster.'));

            return;
        }

        $this->cluster->members()->attach($member->id, [
            'role' => $this->selectedMemberRole,
            'joined_at' => $this->memberJoinedAt,
        ]);

        $this->cluster->refresh();
        $this->closeAddMemberModal();
        $this->dispatch('member-added');
    }

    public function removeMember(string $memberId): void
    {
        $this->authorize('update', $this->cluster);

        $this->cluster->members()->detach($memberId);
        $this->cluster->refresh();
        $this->dispatch('member-removed');
    }

    public function updateMemberRole(string $memberId, string $newRole): void
    {
        $this->authorize('update', $this->cluster);

        if (! in_array($newRole, ['leader', 'assistant', 'member'])) {
            return;
        }

        $this->cluster->members()->updateExistingPivot($memberId, [
            'role' => $newRole,
        ]);

        $this->cluster->refresh();
        $this->dispatch('member-role-updated');
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->cluster);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->cluster);
        $this->cluster->delete();
        $this->dispatch('cluster-deleted');
        $this->redirect(route('clusters.index', $this->branch), navigate: true);
    }

    public function render()
    {
        return view('livewire.clusters.cluster-show');
    }
}
