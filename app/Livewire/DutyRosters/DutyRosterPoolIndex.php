<?php

namespace App\Livewire\DutyRosters;

use App\Enums\DutyRosterRoleType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRosterPool;
use App\Models\Tenant\Member;
use App\Services\DutyRosterGenerationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DutyRosterPoolIndex extends Component
{
    public Branch $branch;

    public string $roleTypeFilter = '';

    // Pool form properties
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showMembersModal = false;

    public string $name = '';

    public string $role_type = 'preacher';

    public string $description = '';

    public bool $is_active = true;

    public ?DutyRosterPool $editingPool = null;

    public ?DutyRosterPool $deletingPool = null;

    public ?DutyRosterPool $managingPool = null;

    // Member management
    public ?string $selectedMemberId = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [DutyRosterPool::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function pools(): Collection
    {
        $query = DutyRosterPool::where('branch_id', $this->branch->id)
            ->withCount('members');

        if ($this->roleTypeFilter) {
            $query->where('role_type', $this->roleTypeFilter);
        }

        return $query->orderBy('role_type')->orderBy('name')->get();
    }

    #[Computed]
    public function roleTypes(): array
    {
        return DutyRosterRoleType::cases();
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function availableMembers(): Collection
    {
        if (! $this->managingPool) {
            return collect();
        }

        $existingMemberIds = $this->managingPool->members->pluck('id');

        return $this->members->reject(fn ($m) => $existingMemberIds->contains($m->id));
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [DutyRosterPool::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return $this->editingPool && auth()->user()->can('delete', $this->editingPool);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role_type' => ['required', Rule::enum(DutyRosterRoleType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [DutyRosterPool::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [DutyRosterPool::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['created_by'] = auth()->id();

        if ($validated['description'] === '') {
            $validated['description'] = null;
        }

        DutyRosterPool::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('pool-created');
    }

    public function edit(DutyRosterPool $pool): void
    {
        $this->authorize('update', $pool);
        $this->editingPool = $pool;
        $this->fill([
            'name' => $pool->name,
            'role_type' => $pool->role_type->value,
            'description' => $pool->description ?? '',
            'is_active' => $pool->is_active,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingPool);
        $validated = $this->validate();

        if ($validated['description'] === '') {
            $validated['description'] = null;
        }

        $this->editingPool->update($validated);

        $this->showEditModal = false;
        $this->editingPool = null;
        $this->resetForm();
        $this->dispatch('pool-updated');
    }

    public function confirmDelete(DutyRosterPool $pool): void
    {
        $this->authorize('delete', $pool);
        $this->deletingPool = $pool;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingPool);
        $this->deletingPool->delete();
        $this->showDeleteModal = false;
        $this->deletingPool = null;
        $this->dispatch('pool-deleted');
    }

    public function manageMembers(DutyRosterPool $pool): void
    {
        $this->authorize('manageMembers', $pool);
        $this->managingPool = $pool->load('members');
        $this->selectedMemberId = null;
        $this->showMembersModal = true;
    }

    public function addMember(): void
    {
        if (! $this->selectedMemberId || ! $this->managingPool) {
            return;
        }

        $this->authorize('manageMembers', $this->managingPool);

        $this->managingPool->members()->attach($this->selectedMemberId, [
            'id' => (string) Str::uuid(),
            'is_active' => true,
            'assignment_count' => 0,
            'sort_order' => $this->managingPool->members()->count(),
        ]);

        $this->managingPool->load('members');
        $this->selectedMemberId = null;
        $this->dispatch('member-added');
    }

    public function removeMember(string $memberId): void
    {
        if (! $this->managingPool) {
            return;
        }

        $this->authorize('manageMembers', $this->managingPool);

        $this->managingPool->members()->detach($memberId);
        $this->managingPool->load('members');
        $this->dispatch('member-removed');
    }

    public function toggleMemberActive(string $memberId): void
    {
        if (! $this->managingPool) {
            return;
        }

        $this->authorize('manageMembers', $this->managingPool);

        $pivot = $this->managingPool->members()->where('member_id', $memberId)->first()?->pivot;
        if ($pivot) {
            $this->managingPool->members()->updateExistingPivot($memberId, [
                'is_active' => ! $pivot->is_active,
            ]);
            $this->managingPool->load('members');
        }
    }

    public function resetMemberCounters(string $memberId): void
    {
        if (! $this->managingPool) {
            return;
        }

        $this->authorize('manageMembers', $this->managingPool);

        $this->managingPool->members()->updateExistingPivot($memberId, [
            'assignment_count' => 0,
            'last_assigned_date' => null,
        ]);
        $this->managingPool->load('members');
        $this->dispatch('counters-reset');
    }

    public function resetAllCounters(): void
    {
        if (! $this->managingPool) {
            return;
        }

        $this->authorize('manageMembers', $this->managingPool);

        app(DutyRosterGenerationService::class)->resetPoolRotation($this->managingPool);
        $this->managingPool->load('members');
        $this->dispatch('all-counters-reset');
    }

    public function closeMembersModal(): void
    {
        $this->showMembersModal = false;
        $this->managingPool = null;
        $this->selectedMemberId = null;
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingPool = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPool = null;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'description']);
        $this->role_type = 'preacher';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.duty-roster-pool-index');
    }
}
