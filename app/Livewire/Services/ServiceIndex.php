<?php

namespace App\Livewire\Services;

use App\Enums\ServiceType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ServiceIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public ?int $day_of_week = null;

    public string $time = '';

    public string $service_type = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    public ?Service $editingService = null;

    public ?Service $deletingService = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Service::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function services(): Collection
    {
        $query = Service::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name']);
        $this->applyEnumFilter($query, 'typeFilter', 'service_type');
        $this->applyBooleanFilter($query, 'statusFilter', 'is_active', 'active');

        return $query->orderBy('day_of_week')->orderBy('time')->get();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->typeFilter)
            || $this->isFilterActive($this->statusFilter);
    }

    #[Computed]
    public function serviceTypes(): array
    {
        return ServiceType::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Service::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Service::class, $this->branch]);
    }

    public function getDayName(?int $day): string
    {
        if ($day === null) {
            return '-';
        }

        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day] ?? '-';
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time' => ['required', 'date_format:H:i'],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Service::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Service::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

        // Convert empty capacity to null
        if (isset($validated['capacity']) && $validated['capacity'] === '') {
            $validated['capacity'] = null;
        }

        Service::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('service-created');
    }

    public function edit(Service $service): void
    {
        $this->authorize('update', $service);
        $this->editingService = $service;
        $this->fill([
            'name' => $service->name,
            'day_of_week' => $service->day_of_week,
            'time' => substr($service->time, 0, 5), // Format as H:i
            'service_type' => $service->service_type->value,
            'capacity' => $service->capacity,
            'is_active' => $service->is_active,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingService);
        $validated = $this->validate();

        // Convert empty capacity to null
        if (isset($validated['capacity']) && $validated['capacity'] === '') {
            $validated['capacity'] = null;
        }

        $this->editingService->update($validated);

        $this->showEditModal = false;
        $this->editingService = null;
        $this->resetForm();
        $this->dispatch('service-updated');
    }

    public function confirmDelete(Service $service): void
    {
        $this->authorize('delete', $service);
        $this->deletingService = $service;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingService);
        $this->deletingService->delete();
        $this->showDeleteModal = false;
        $this->deletingService = null;
        $this->dispatch('service-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingService = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingService = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'day_of_week', 'time', 'service_type', 'capacity',
        ]);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.services.service-index');
    }
}
