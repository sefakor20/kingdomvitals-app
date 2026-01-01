<?php

namespace App\Livewire\Services;

use App\Enums\ServiceType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ServiceShow extends Component
{
    public Branch $branch;

    public Service $service;

    public bool $editing = false;

    // Form fields
    public string $name = '';

    public ?int $day_of_week = null;

    public string $time = '';

    public string $service_type = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    // Delete modal
    public bool $showDeleteModal = false;

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('view', $service);
        $this->branch = $branch;
        $this->service = $service;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->service);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->service);
    }

    #[Computed]
    public function serviceTypes(): array
    {
        return ServiceType::cases();
    }

    #[Computed]
    public function attendanceCount(): int
    {
        return $this->service->attendance()->count();
    }

    #[Computed]
    public function donationCount(): int
    {
        return $this->service->donations()->count();
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

    public function edit(): void
    {
        $this->authorize('update', $this->service);

        $this->fill([
            'name' => $this->service->name,
            'day_of_week' => $this->service->day_of_week,
            'time' => substr($this->service->time, 0, 5), // Format as H:i
            'service_type' => $this->service->service_type->value,
            'capacity' => $this->service->capacity,
            'is_active' => $this->service->is_active,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->service);
        $validated = $this->validate();

        // Convert empty capacity to null
        if (isset($validated['capacity']) && $validated['capacity'] === '') {
            $validated['capacity'] = null;
        }

        $this->service->update($validated);
        $this->service->refresh();

        $this->editing = false;
        $this->dispatch('service-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->service);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->service);
        $this->service->delete();
        $this->dispatch('service-deleted');
        $this->redirect(route('services.index', $this->branch), navigate: true);
    }

    public function render()
    {
        return view('livewire.services.service-show');
    }
}
