<?php

namespace App\Livewire\DutyRosters;

use App\Enums\DutyRosterStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DutyRosterIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    public string $search = '';

    public string $statusFilter = '';

    public ?string $monthFilter = null;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public ?string $service_id = null;

    public string $service_date = '';

    public string $theme = '';

    public ?string $preacher_id = null;

    public string $preacher_name = '';

    public ?string $liturgist_id = null;

    public string $liturgist_name = '';

    public array $hymn_numbers = [];

    public string $remarks = '';

    public string $status = 'draft';

    public ?DutyRoster $editingRoster = null;

    public ?DutyRoster $deletingRoster = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [DutyRoster::class, $branch]);
        $this->branch = $branch;
        $this->monthFilter = now()->format('Y-m');
    }

    #[Computed]
    public function dutyRosters(): Collection
    {
        $query = DutyRoster::where('branch_id', $this->branch->id)
            ->with(['service', 'preacher', 'liturgist', 'clusters'])
            ->withCount('scriptures');

        $this->applySearch($query, ['theme', 'preacher_name', 'liturgist_name', 'remarks']);
        $this->applyEnumFilter($query, 'statusFilter', 'status');

        // Apply month filter
        if ($this->monthFilter) {
            $date = Carbon::parse($this->monthFilter.'-01');
            $query->whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month);
        }

        return $query->orderBy('service_date')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return DutyRosterStatus::cases();
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
    public function clusters(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [DutyRoster::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [DutyRoster::class, $this->branch]);
    }

    protected function rules(): array
    {
        return [
            'service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'service_date' => ['required', 'date'],
            'theme' => ['nullable', 'string', 'max:255'],
            'preacher_id' => ['nullable', 'uuid', 'exists:members,id'],
            'preacher_name' => ['nullable', 'string', 'max:100'],
            'liturgist_id' => ['nullable', 'uuid', 'exists:members,id'],
            'liturgist_name' => ['nullable', 'string', 'max:100'],
            'hymn_numbers' => ['nullable', 'array'],
            'hymn_numbers.*' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(DutyRosterStatus::class)],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [DutyRoster::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [DutyRoster::class, $this->branch]);

        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['created_by'] = auth()->id();

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'service_id', 'theme', 'preacher_id', 'preacher_name',
            'liturgist_id', 'liturgist_name', 'remarks',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Filter out empty hymn numbers
        $validated['hymn_numbers'] = array_values(array_filter($validated['hymn_numbers'] ?? [], fn ($h) => $h !== null && $h !== ''));

        DutyRoster::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('roster-created');
    }

    public function edit(DutyRoster $dutyRoster): void
    {
        $this->authorize('update', $dutyRoster);
        $this->editingRoster = $dutyRoster;
        $this->fill([
            'service_id' => $dutyRoster->service_id,
            'service_date' => $dutyRoster->service_date->format('Y-m-d'),
            'theme' => $dutyRoster->theme ?? '',
            'preacher_id' => $dutyRoster->preacher_id,
            'preacher_name' => $dutyRoster->preacher_name ?? '',
            'liturgist_id' => $dutyRoster->liturgist_id,
            'liturgist_name' => $dutyRoster->liturgist_name ?? '',
            'hymn_numbers' => $dutyRoster->hymn_numbers ?? [],
            'remarks' => $dutyRoster->remarks ?? '',
            'status' => $dutyRoster->status->value,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingRoster);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'service_id', 'theme', 'preacher_id', 'preacher_name',
            'liturgist_id', 'liturgist_name', 'remarks',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Filter out empty hymn numbers
        $validated['hymn_numbers'] = array_values(array_filter($validated['hymn_numbers'] ?? [], fn ($h) => $h !== null && $h !== ''));

        $this->editingRoster->update($validated);

        $this->showEditModal = false;
        $this->editingRoster = null;
        $this->resetForm();
        $this->dispatch('roster-updated');
    }

    public function confirmDelete(DutyRoster $dutyRoster): void
    {
        $this->authorize('delete', $dutyRoster);
        $this->deletingRoster = $dutyRoster;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingRoster);
        $this->deletingRoster->delete();
        $this->showDeleteModal = false;
        $this->deletingRoster = null;
        $this->dispatch('roster-deleted');
    }

    public function togglePublish(DutyRoster $dutyRoster): void
    {
        $this->authorize('publish', $dutyRoster);

        if ($dutyRoster->is_published) {
            $dutyRoster->unpublish();
            $this->dispatch('roster-unpublished');
        } else {
            $dutyRoster->publish();
            $this->dispatch('roster-published');
        }
    }

    public function addHymn(): void
    {
        $this->hymn_numbers[] = null;
    }

    public function removeHymn(int $index): void
    {
        unset($this->hymn_numbers[$index]);
        $this->hymn_numbers = array_values($this->hymn_numbers);
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingRoster = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingRoster = null;
    }

    public function previousMonth(): void
    {
        $date = Carbon::parse($this->monthFilter.'-01')->subMonth();
        $this->monthFilter = $date->format('Y-m');
    }

    public function nextMonth(): void
    {
        $date = Carbon::parse($this->monthFilter.'-01')->addMonth();
        $this->monthFilter = $date->format('Y-m');
    }

    private function resetForm(): void
    {
        $this->reset([
            'service_id', 'service_date', 'theme', 'preacher_id', 'preacher_name',
            'liturgist_id', 'liturgist_name', 'hymn_numbers', 'remarks',
        ]);
        $this->status = 'draft';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.duty-roster-index');
    }
}
