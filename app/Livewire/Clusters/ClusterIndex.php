<?php

namespace App\Livewire\Clusters;

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
class ClusterIndex extends Component
{
    public Branch $branch;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
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

    public ?Cluster $editingCluster = null;

    public ?Cluster $deletingCluster = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Cluster::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function clusters(): Collection
    {
        $query = Cluster::where('branch_id', $this->branch->id)
            ->withCount('members')
            ->with(['leader', 'assistantLeader']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->typeFilter) {
            $query->where('cluster_type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function clusterTypes(): array
    {
        return ClusterType::cases();
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
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Cluster::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Cluster::class, $this->branch]);
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

    public function create(): void
    {
        $this->authorize('create', [Cluster::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Cluster::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

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

        Cluster::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('cluster-created');
    }

    public function edit(Cluster $cluster): void
    {
        $this->authorize('update', $cluster);
        $this->editingCluster = $cluster;
        $this->fill([
            'name' => $cluster->name,
            'cluster_type' => $cluster->cluster_type->value,
            'description' => $cluster->description ?? '',
            'leader_id' => $cluster->leader_id,
            'assistant_leader_id' => $cluster->assistant_leader_id,
            'meeting_day' => $cluster->meeting_day ?? '',
            'meeting_time' => $cluster->meeting_time,
            'meeting_location' => $cluster->meeting_location ?? '',
            'capacity' => $cluster->capacity,
            'is_active' => $cluster->is_active,
            'notes' => $cluster->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingCluster);
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

        $this->editingCluster->update($validated);

        $this->showEditModal = false;
        $this->editingCluster = null;
        $this->resetForm();
        $this->dispatch('cluster-updated');
    }

    public function confirmDelete(Cluster $cluster): void
    {
        $this->authorize('delete', $cluster);
        $this->deletingCluster = $cluster;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingCluster);
        $this->deletingCluster->delete();
        $this->showDeleteModal = false;
        $this->deletingCluster = null;
        $this->dispatch('cluster-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingCluster = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingCluster = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'cluster_type', 'description', 'leader_id',
            'assistant_leader_id', 'meeting_day', 'meeting_time',
            'meeting_location', 'capacity', 'notes',
        ]);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.clusters.cluster-index');
    }
}
