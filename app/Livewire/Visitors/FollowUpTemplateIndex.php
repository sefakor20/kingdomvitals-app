<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FollowUpTemplate;
use App\Services\FollowUpTemplatePlaceholderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FollowUpTemplateIndex extends Component
{
    use AuthorizesRequests;
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

    public ?string $type = null;

    public bool $is_active = true;

    public ?FollowUpTemplate $editingTemplate = null;

    public ?FollowUpTemplate $deletingTemplate = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [FollowUpTemplate::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function templates(): Collection
    {
        $query = FollowUpTemplate::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'body']);

        if ($this->typeFilter !== '') {
            if ($this->typeFilter === 'generic') {
                $query->whereNull('type');
            } else {
                $query->where('type', $this->typeFilter);
            }
        }

        $this->applyBooleanFilter($query, 'statusFilter', 'is_active', 'active');

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function followUpTypes(): array
    {
        return FollowUpType::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->can('create', [FollowUpTemplate::class, $this->branch]) ?? false;
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
        return app(FollowUpTemplatePlaceholderService::class)->getAvailablePlaceholders();
    }

    protected function rules(): array
    {
        $followUpTypes = collect(FollowUpType::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:1', 'max:1600'],
            'type' => ['nullable', 'string', 'in:'.$followUpTypes],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [FollowUpTemplate::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [FollowUpTemplate::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['type'] = $validated['type'] ?: null;

        FollowUpTemplate::create($validated);

        unset($this->templates);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('template-created');
    }

    public function edit(FollowUpTemplate $template): void
    {
        $this->authorize('update', $template);
        $this->editingTemplate = $template;
        $this->fill([
            'name' => $template->name,
            'body' => $template->body,
            'type' => $template->type?->value,
            'is_active' => $template->is_active,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingTemplate);
        $validated = $this->validate();

        $validated['type'] = $validated['type'] ?: null;

        $this->editingTemplate->update($validated);

        unset($this->templates);

        $this->showEditModal = false;
        $this->editingTemplate = null;
        $this->resetForm();
        $this->dispatch('template-updated');
    }

    public function confirmDelete(FollowUpTemplate $template): void
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

    public function toggleActive(FollowUpTemplate $template): void
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
        $this->type = null;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.visitors.follow-up-template-index');
    }
}
