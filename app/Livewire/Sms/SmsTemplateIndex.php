<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\SmsType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SmsTemplateIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public string $body = '';

    public string $type = 'custom';

    public bool $is_active = true;

    public ?SmsTemplate $editingTemplate = null;

    public ?SmsTemplate $deletingTemplate = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [SmsTemplate::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function templates(): Collection
    {
        $query = SmsTemplate::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'body']);
        $this->applyEnumFilter($query, 'typeFilter', 'type');
        $this->applyBooleanFilter($query, 'statusFilter', 'is_active', 'active');

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function smsTypes(): array
    {
        return SmsType::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [SmsTemplate::class, $this->branch]);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->typeFilter)) {
            return true;
        }
        return $this->isFilterActive($this->statusFilter);
    }

    #[Computed]
    public function availablePlaceholders(): array
    {
        return $this->getPlaceholdersForType($this->type);
    }

    /**
     * Get available placeholders for a given SMS type.
     *
     * @return array<string, string>
     */
    public function getPlaceholdersForType(string $type): array
    {
        $basePlaceholders = [
            '{first_name}' => 'Member\'s first name',
            '{last_name}' => 'Member\'s last name',
            '{full_name}' => 'Member\'s full name',
        ];

        $branchPlaceholder = [
            '{branch_name}' => 'Branch name',
        ];

        $servicePlaceholders = [
            '{service_name}' => 'Service name (e.g., "Sunday Service")',
            '{service_day}' => 'Day of service (e.g., "Sunday")',
        ];

        $serviceTimePlaceholder = [
            '{service_time}' => 'Service time (e.g., "9:00 AM")',
        ];

        $dutyRosterPlaceholders = [
            '{role}' => 'Assigned role (e.g., "Preacher", "Liturgist", "Reader")',
            '{service_date}' => 'Service date (e.g., "Sunday, Jan 28")',
            '{theme}' => 'Service theme (if set)',
        ];

        return match ($type) {
            'birthday' => $basePlaceholders,
            'welcome' => array_merge($basePlaceholders, $branchPlaceholder),
            'reminder' => array_merge($basePlaceholders, $branchPlaceholder, $servicePlaceholders, $serviceTimePlaceholder),
            'follow_up' => array_merge($basePlaceholders, $branchPlaceholder, $servicePlaceholders),
            'duty_roster_reminder' => array_merge($basePlaceholders, $branchPlaceholder, $dutyRosterPlaceholders),
            default => array_merge($basePlaceholders, $branchPlaceholder),
        };
    }

    protected function rules(): array
    {
        $smsTypes = collect(SmsType::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:1', 'max:1600'],
            'type' => ['required', 'string', 'in:'.$smsTypes],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [SmsTemplate::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [SmsTemplate::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

        SmsTemplate::create($validated);

        unset($this->templates);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('template-created');
    }

    public function edit(SmsTemplate $template): void
    {
        $this->authorize('update', $template);
        $this->editingTemplate = $template;
        $this->fill([
            'name' => $template->name,
            'body' => $template->body,
            'type' => $template->type?->value ?? 'custom',
            'is_active' => $template->is_active,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingTemplate);
        $validated = $this->validate();

        $this->editingTemplate->update($validated);

        unset($this->templates);

        $this->showEditModal = false;
        $this->editingTemplate = null;
        $this->resetForm();
        $this->dispatch('template-updated');
    }

    public function confirmDelete(SmsTemplate $template): void
    {
        $this->authorize('delete', $template);
        $this->deletingTemplate = $template;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingTemplate);

        $this->deletingTemplate->delete();

        unset($this->templates);

        $this->showDeleteModal = false;
        $this->deletingTemplate = null;
        $this->dispatch('template-deleted');
    }

    public function toggleActive(SmsTemplate $template): void
    {
        $this->authorize('update', $template);

        $template->update(['is_active' => ! $template->is_active]);

        unset($this->templates);
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingTemplate = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingTemplate = null;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter']);
        unset($this->templates);
        unset($this->hasActiveFilters);
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'body']);
        $this->type = 'custom';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.sms.sms-template-index');
    }
}
