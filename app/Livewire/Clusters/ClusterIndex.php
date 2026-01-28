<?php

namespace App\Livewire\Clusters;

use App\Enums\ClusterType;
use App\Enums\QuotaType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasQuotaComputed;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ClusterIndex extends Component
{
    use HasFilterableQuery;
    use HasQuotaComputed;
    use WithPagination;

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
    public function clusters(): LengthAwarePaginator
    {
        $query = Cluster::where('branch_id', $this->branch->id)
            ->withCount('members')
            ->with(['leader', 'assistantLeader']);

        $this->applySearch($query, ['name']);
        $this->applyEnumFilter($query, 'typeFilter', 'cluster_type');
        $this->applyBooleanFilter($query, 'statusFilter', 'is_active', 'active');

        return $query->orderBy('name')->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
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

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return $this->showQuotaWarningFor(QuotaType::Clusters);
    }

    /**
     * Check if cluster creation is allowed based on quota.
     */
    #[Computed]
    public function canCreateWithinQuota(): bool
    {
        return $this->canCreateWithinQuotaFor(QuotaType::Clusters);
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

        // Check quota before creating
        if (! $this->canCreateWithinQuota) {
            $this->addError('name', 'You have reached your cluster limit. Please upgrade your plan to add more clusters.');

            return;
        }

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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.clusters.cluster-index');
    }
}
