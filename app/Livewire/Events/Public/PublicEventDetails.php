<?php

declare(strict_types=1);

namespace App\Livewire\Events\Public;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class PublicEventDetails extends Component
{
    public Branch $branch;

    public Event $event;

    public function mount(Branch $branch, Event $event): void
    {
        // Validate event is publicly visible
        if (! $event->is_public || $event->visibility !== EventVisibility::Public) {
            abort(404);
        }

        // Validate event status
        if (! in_array($event->status, [EventStatus::Published, EventStatus::Ongoing, EventStatus::Completed])) {
            abort(404);
        }

        $this->branch = $branch;
        $this->event = $event;
    }

    #[Computed]
    public function spotsRemaining(): ?int
    {
        return $this->event->available_spots;
    }

    #[Computed]
    public function canRegister(): bool
    {
        return $this->event->canRegister();
    }

    #[Computed]
    public function registrationMessage(): ?string
    {
        if (! $this->event->allow_registration) {
            return __('Registration is not available for this event.');
        }

        if ($this->event->is_full) {
            return __('This event is fully booked.');
        }

        if ($this->event->status === EventStatus::Completed) {
            return __('This event has already ended.');
        }

        if ($this->event->status === EventStatus::Cancelled) {
            return __('This event has been cancelled.');
        }

        if ($this->event->registration_opens_at && $this->event->registration_opens_at->isFuture()) {
            return __('Registration opens :date', ['date' => $this->event->registration_opens_at->format('M j, Y')]);
        }

        if ($this->event->registration_closes_at && $this->event->registration_closes_at->isPast()) {
            return __('Registration has closed.');
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.events.public.public-event-details');
    }
}
