<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class EventIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

    public Branch $branch;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $timeFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public string $description = '';

    public string $event_type = '';

    public string $category = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $location = '';

    public string $address = '';

    public string $city = '';

    public ?int $capacity = null;

    public bool $allow_registration = true;

    public string $registration_opens_at = '';

    public string $registration_closes_at = '';

    public bool $is_paid = false;

    public ?float $price = null;

    public string $visibility = 'public';

    public string $status = 'draft';

    public ?Event $editingEvent = null;

    public ?Event $deletingEvent = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Event::class, $branch]);
        $this->branch = $branch;
    }

    /**
     * @return Builder<Event>
     */
    #[Computed]
    public function eventsQuery(): Builder
    {
        $query = Event::query()
            ->where('branch_id', $this->branch->id)
            ->with(['organizer:id,first_name,last_name']);

        $this->applySearch($query, ['name', 'location', 'description']);
        $this->applyEnumFilter($query, 'typeFilter', 'event_type');
        $this->applyEnumFilter($query, 'statusFilter', 'status');

        // Time filter: upcoming, ongoing, past
        if ($this->timeFilter === 'upcoming') {
            $query->where('starts_at', '>', now());
        } elseif ($this->timeFilter === 'ongoing') {
            $query->where('starts_at', '<=', now())
                ->where(function (Builder $q): void {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                });
        } elseif ($this->timeFilter === 'past') {
            $query->where(function (Builder $q): void {
                $q->whereNotNull('ends_at')
                    ->where('ends_at', '<', now());
            });
        }

        return $query->orderBy('starts_at', 'desc');
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->typeFilter)
            || $this->isFilterActive($this->statusFilter)
            || $this->isFilterActive($this->timeFilter);
    }

    /**
     * @return array<EventType>
     */
    #[Computed]
    public function eventTypes(): array
    {
        return EventType::cases();
    }

    /**
     * @return array<EventStatus>
     */
    #[Computed]
    public function eventStatuses(): array
    {
        return EventStatus::cases();
    }

    /**
     * @return array<EventVisibility>
     */
    #[Computed]
    public function visibilityOptions(): array
    {
        return EventVisibility::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Event::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Event::class, $this->branch]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'category' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'allow_registration' => ['boolean'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
            'is_paid' => ['boolean'],
            'price' => ['nullable', 'required_if:is_paid,true', 'numeric', 'min:0'],
            'visibility' => ['required', Rule::enum(EventVisibility::class)],
            'status' => ['required', Rule::enum(EventStatus::class)],
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter', 'timeFilter']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', [Event::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Event::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = 'GHS';
        $validated['requires_ticket'] = true;
        $validated['is_public'] = $validated['visibility'] === EventVisibility::Public->value;

        // Convert empty values to null
        foreach (['capacity', 'ends_at', 'registration_opens_at', 'registration_closes_at', 'price'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        Event::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('event-created');
    }

    public function edit(Event $event): void
    {
        $this->authorize('update', $event);
        $this->editingEvent = $event;
        $this->fill([
            'name' => $event->name,
            'description' => $event->description ?? '',
            'event_type' => $event->event_type->value,
            'category' => $event->category ?? '',
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
            'ends_at' => $event->ends_at?->format('Y-m-d\TH:i') ?? '',
            'location' => $event->location,
            'address' => $event->address ?? '',
            'city' => $event->city ?? '',
            'capacity' => $event->capacity,
            'allow_registration' => $event->allow_registration,
            'registration_opens_at' => $event->registration_opens_at?->format('Y-m-d\TH:i') ?? '',
            'registration_closes_at' => $event->registration_closes_at?->format('Y-m-d\TH:i') ?? '',
            'is_paid' => $event->is_paid,
            'price' => $event->price ? (float) $event->price : null,
            'visibility' => $event->visibility->value,
            'status' => $event->status->value,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingEvent);
        $validated = $this->validate();

        $validated['is_public'] = $validated['visibility'] === EventVisibility::Public->value;

        // Convert empty values to null
        foreach (['capacity', 'ends_at', 'registration_opens_at', 'registration_closes_at', 'price'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingEvent->update($validated);

        $this->showEditModal = false;
        $this->editingEvent = null;
        $this->resetForm();
        $this->dispatch('event-updated');
    }

    public function confirmDelete(Event $event): void
    {
        $this->authorize('delete', $event);
        $this->deletingEvent = $event;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingEvent);
        $this->deletingEvent->delete();
        $this->showDeleteModal = false;
        $this->deletingEvent = null;
        $this->dispatch('event-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingEvent = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingEvent = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'description', 'event_type', 'category',
            'starts_at', 'ends_at', 'location', 'address', 'city',
            'capacity', 'registration_opens_at', 'registration_closes_at',
            'price',
        ]);
        $this->allow_registration = true;
        $this->is_paid = false;
        $this->visibility = 'public';
        $this->status = 'draft';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.events.event-index', [
            'events' => $this->eventsQuery->paginate(15),
        ]);
    }
}
