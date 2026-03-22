<?php

declare(strict_types=1);

namespace App\Livewire\Email;

use App\Enums\EmailType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailTemplate;
use App\Services\BulkEmailService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EmailTemplateIndex extends Component
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

    public string $subject = '';

    public string $body = '';

    public string $type = 'custom';

    public bool $is_active = true;

    public ?EmailTemplate $editingTemplate = null;

    public ?EmailTemplate $deletingTemplate = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [EmailTemplate::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function templates(): Collection
    {
        $query = EmailTemplate::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'subject', 'body']);
        $this->applyEnumFilter($query, 'typeFilter', 'type');
        $this->applyBooleanFilter($query, 'statusFilter', 'is_active', 'active');

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function emailTypes(): array
    {
        return EmailType::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [EmailTemplate::class, $this->branch]);
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
        return BulkEmailService::getAvailablePlaceholders();
    }

    /**
     * Get the Markdown formatting guide.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function markdownGuide(): array
    {
        return BulkEmailService::getMarkdownGuide();
    }

    /**
     * Get the body content rendered as HTML from Markdown.
     */
    #[Computed]
    public function bodyPreview(): string
    {
        if (empty($this->body)) {
            return '';
        }

        return BulkEmailService::markdownToHtml($this->body);
    }

    /**
     * Get available placeholders for a given email type.
     *
     * @return array<string, string>
     */
    public function getPlaceholdersForType(string $type): array
    {
        $basePlaceholders = [
            '{first_name}' => 'Member\'s first name',
            '{last_name}' => 'Member\'s last name',
            '{full_name}' => 'Member\'s full name',
            '{email}' => 'Member\'s email address',
        ];

        $branchPlaceholder = [
            '{branch_name}' => 'Branch name',
        ];

        $datePlaceholders = [
            '{month}' => 'Current month name',
            '{year}' => 'Current year',
        ];

        $eventPlaceholders = [
            '{event_name}' => 'Event name',
            '{event_date}' => 'Event date',
            '{event_time}' => 'Event time',
            '{event_location}' => 'Event location',
        ];

        return match ($type) {
            'birthday' => $basePlaceholders,
            'welcome' => array_merge($basePlaceholders, $branchPlaceholder),
            'newsletter' => array_merge($basePlaceholders, $branchPlaceholder, $datePlaceholders),
            'event_reminder' => array_merge($basePlaceholders, $branchPlaceholder, $eventPlaceholders),
            default => array_merge($basePlaceholders, $branchPlaceholder),
        };
    }

    protected function rules(): array
    {
        $emailTypes = collect(EmailType::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:1', 'max:65000'],
            'type' => ['required', 'string', 'in:'.$emailTypes],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [EmailTemplate::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [EmailTemplate::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

        EmailTemplate::create($validated);

        unset($this->templates);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('template-created');
    }

    public function edit(EmailTemplate $template): void
    {
        $this->authorize('update', $template);
        $this->editingTemplate = $template;
        $this->fill([
            'name' => $template->name,
            'subject' => $template->subject,
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

    public function confirmDelete(EmailTemplate $template): void
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

    public function toggleActive(EmailTemplate $template): void
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
        $this->reset(['name', 'subject', 'body']);
        $this->type = 'custom';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): Factory|View
    {
        return view('livewire.email.email-template-index');
    }
}
