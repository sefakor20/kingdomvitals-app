<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SmsTemplateIndex extends Component
{
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

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

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
        return $this->search !== ''
            || $this->typeFilter !== ''
            || $this->statusFilter !== '';
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

    public function render()
    {
        return view('livewire.sms.sms-template-index');
    }
}
