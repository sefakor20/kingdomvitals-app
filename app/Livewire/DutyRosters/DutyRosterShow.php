<?php

namespace App\Livewire\DutyRosters;

use App\Enums\DutyRosterStatus;
use App\Enums\ScriptureReadingType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\DutyRosterScripture;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DutyRosterShow extends Component
{
    public Branch $branch;

    public DutyRoster $dutyRoster;

    public bool $editing = false;

    // Main form fields
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

    // Scripture management
    public bool $showAddScriptureModal = false;

    public string $scripture_reference = '';

    public string $scripture_reading_type = '';

    public ?string $scripture_reader_id = null;

    public string $scripture_reader_name = '';

    public ?DutyRosterScripture $editingScripture = null;

    // Cluster management
    public bool $showAddClusterModal = false;

    public string $selectedClusterId = '';

    public string $clusterNotes = '';

    // Delete modal
    public bool $showDeleteModal = false;

    public bool $showDeleteScriptureModal = false;

    public ?DutyRosterScripture $deletingScripture = null;

    public function mount(Branch $branch, DutyRoster $dutyRoster): void
    {
        $this->authorize('view', $dutyRoster);
        $this->branch = $branch;
        $this->dutyRoster = $dutyRoster->load(['service', 'preacher', 'liturgist', 'scriptures.reader', 'clusters']);
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->dutyRoster);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->dutyRoster);
    }

    #[Computed]
    public function canPublish(): bool
    {
        return auth()->user()->can('publish', $this->dutyRoster);
    }

    #[Computed]
    public function statuses(): array
    {
        return DutyRosterStatus::cases();
    }

    #[Computed]
    public function readingTypes(): array
    {
        return ScriptureReadingType::cases();
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
    public function availableClusters(): Collection
    {
        $existingClusterIds = $this->dutyRoster->clusters()->pluck('clusters.id')->toArray();

        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->whereNotIn('id', $existingClusterIds)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function scriptures(): Collection
    {
        return $this->dutyRoster->scriptures()->with('reader')->orderBy('sort_order')->get();
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

    public function edit(): void
    {
        $this->authorize('update', $this->dutyRoster);

        $this->fill([
            'service_id' => $this->dutyRoster->service_id,
            'service_date' => $this->dutyRoster->service_date->format('Y-m-d'),
            'theme' => $this->dutyRoster->theme ?? '',
            'preacher_id' => $this->dutyRoster->preacher_id,
            'preacher_name' => $this->dutyRoster->preacher_name ?? '',
            'liturgist_id' => $this->dutyRoster->liturgist_id,
            'liturgist_name' => $this->dutyRoster->liturgist_name ?? '',
            'hymn_numbers' => $this->dutyRoster->hymn_numbers ?? [],
            'remarks' => $this->dutyRoster->remarks ?? '',
            'status' => $this->dutyRoster->status->value,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->dutyRoster);
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

        $this->dutyRoster->update($validated);
        $this->dutyRoster->refresh();

        $this->editing = false;
        $this->dispatch('roster-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
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

    // Scripture Management
    public function openAddScriptureModal(): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->resetScriptureForm();
        $this->showAddScriptureModal = true;
    }

    public function closeAddScriptureModal(): void
    {
        $this->showAddScriptureModal = false;
        $this->resetScriptureForm();
    }

    public function addScripture(): void
    {
        $this->authorize('update', $this->dutyRoster);

        $validated = $this->validate([
            'scripture_reference' => ['required', 'string', 'max:100'],
            'scripture_reading_type' => ['nullable', Rule::enum(ScriptureReadingType::class)],
            'scripture_reader_id' => ['nullable', 'uuid', 'exists:members,id'],
            'scripture_reader_name' => ['nullable', 'string', 'max:100'],
        ]);

        $maxOrder = $this->dutyRoster->scriptures()->max('sort_order') ?? -1;

        $this->dutyRoster->scriptures()->create([
            'reference' => $validated['scripture_reference'],
            'reading_type' => $validated['scripture_reading_type'] ?: null,
            'reader_id' => $validated['scripture_reader_id'] ?: null,
            'reader_name' => $validated['scripture_reader_name'] ?: null,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->dutyRoster->refresh();
        $this->closeAddScriptureModal();
        $this->dispatch('scripture-added');
    }

    public function editScripture(DutyRosterScripture $scripture): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->editingScripture = $scripture;
        $this->scripture_reference = $scripture->reference;
        $this->scripture_reading_type = $scripture->reading_type?->value ?? '';
        $this->scripture_reader_id = $scripture->reader_id;
        $this->scripture_reader_name = $scripture->reader_name ?? '';
        $this->showAddScriptureModal = true;
    }

    public function updateScripture(): void
    {
        $this->authorize('update', $this->dutyRoster);

        $validated = $this->validate([
            'scripture_reference' => ['required', 'string', 'max:100'],
            'scripture_reading_type' => ['nullable', Rule::enum(ScriptureReadingType::class)],
            'scripture_reader_id' => ['nullable', 'uuid', 'exists:members,id'],
            'scripture_reader_name' => ['nullable', 'string', 'max:100'],
        ]);

        $this->editingScripture->update([
            'reference' => $validated['scripture_reference'],
            'reading_type' => $validated['scripture_reading_type'] ?: null,
            'reader_id' => $validated['scripture_reader_id'] ?: null,
            'reader_name' => $validated['scripture_reader_name'] ?: null,
        ]);

        $this->dutyRoster->refresh();
        $this->closeAddScriptureModal();
        $this->dispatch('scripture-updated');
    }

    public function confirmDeleteScripture(DutyRosterScripture $scripture): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->deletingScripture = $scripture;
        $this->showDeleteScriptureModal = true;
    }

    public function deleteScripture(): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->deletingScripture->delete();
        $this->dutyRoster->refresh();
        $this->showDeleteScriptureModal = false;
        $this->deletingScripture = null;
        $this->dispatch('scripture-deleted');
    }

    public function cancelDeleteScripture(): void
    {
        $this->showDeleteScriptureModal = false;
        $this->deletingScripture = null;
    }

    private function resetScriptureForm(): void
    {
        $this->scripture_reference = '';
        $this->scripture_reading_type = '';
        $this->scripture_reader_id = null;
        $this->scripture_reader_name = '';
        $this->editingScripture = null;
    }

    // Cluster Management
    public function openAddClusterModal(): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->reset(['selectedClusterId', 'clusterNotes']);
        $this->showAddClusterModal = true;
    }

    public function closeAddClusterModal(): void
    {
        $this->showAddClusterModal = false;
        $this->reset(['selectedClusterId', 'clusterNotes']);
    }

    public function addCluster(): void
    {
        $this->authorize('update', $this->dutyRoster);

        $this->validate([
            'selectedClusterId' => ['required', 'uuid', 'exists:clusters,id'],
            'clusterNotes' => ['nullable', 'string'],
        ]);

        $this->dutyRoster->clusters()->attach($this->selectedClusterId, [
            'notes' => $this->clusterNotes ?: null,
        ]);

        $this->dutyRoster->refresh();
        $this->closeAddClusterModal();
        $this->dispatch('cluster-added');
    }

    public function removeCluster(string $clusterId): void
    {
        $this->authorize('update', $this->dutyRoster);
        $this->dutyRoster->clusters()->detach($clusterId);
        $this->dutyRoster->refresh();
        $this->dispatch('cluster-removed');
    }

    // Publishing
    public function togglePublish(): void
    {
        $this->authorize('publish', $this->dutyRoster);

        if ($this->dutyRoster->is_published) {
            $this->dutyRoster->unpublish();
            $this->dispatch('roster-unpublished');
        } else {
            $this->dutyRoster->publish();
            $this->dispatch('roster-published');
        }

        $this->dutyRoster->refresh();
    }

    // Delete
    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->dutyRoster);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->dutyRoster);
        $this->dutyRoster->delete();
        $this->dispatch('roster-deleted');
        $this->redirect(route('duty-rosters.index', $this->branch), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.duty-roster-show');
    }
}
