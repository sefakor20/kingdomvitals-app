<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Enums\CheckInMethod;
use App\Enums\RegistrationStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EventShow extends Component
{
    public Branch $branch;

    public Event $event;

    public bool $showAddRegistrationModal = false;

    public bool $showEditRegistrationModal = false;

    public bool $showCancelRegistrationModal = false;

    // Form properties
    public string $registrationType = 'member';

    public string $member_id = '';

    public string $visitor_id = '';

    public string $guest_name = '';

    public string $guest_email = '';

    public string $guest_phone = '';

    public ?EventRegistration $editingRegistration = null;

    public ?EventRegistration $cancellingRegistration = null;

    public function mount(Branch $branch, Event $event): void
    {
        $this->authorize('view', $event);
        $this->branch = $branch;
        $this->event = $event;
    }

    #[Computed]
    public function registrations(): Collection
    {
        return $this->event->registrations()
            ->with(['member:id,first_name,last_name,email,phone', 'visitor:id,first_name,last_name,email,phone'])
            ->latest('registered_at')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $registrations = $this->event->registrations();

        return [
            'total' => $registrations->clone()->whereNotIn('status', [RegistrationStatus::Cancelled->value])->count(),
            'attended' => $registrations->clone()->where('status', RegistrationStatus::Attended->value)->count(),
            'no_show' => $registrations->clone()->where('status', RegistrationStatus::NoShow->value)->count(),
            'cancelled' => $registrations->clone()->where('status', RegistrationStatus::Cancelled->value)->count(),
        ];
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereNotIn('id', function ($query): void {
                $query->select('member_id')
                    ->from('event_registrations')
                    ->where('event_id', $this->event->id)
                    ->whereNotNull('member_id')
                    ->where('status', '!=', RegistrationStatus::Cancelled->value);
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    #[Computed]
    public function visitors(): Collection
    {
        return Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotIn('id', function ($query): void {
                $query->select('visitor_id')
                    ->from('event_registrations')
                    ->where('event_id', $this->event->id)
                    ->whereNotNull('visitor_id')
                    ->where('status', '!=', RegistrationStatus::Cancelled->value);
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    #[Computed]
    public function canManageRegistrations(): bool
    {
        return auth()->user()->can('manageRegistrations', $this->event);
    }

    #[Computed]
    public function canCheckIn(): bool
    {
        return auth()->user()->can('checkIn', $this->event);
    }

    #[Computed]
    public function publicRegistrationUrl(): string
    {
        return route('events.public.register', [$this->branch, $this->event]);
    }

    public function openAddRegistration(): void
    {
        $this->authorize('manageRegistrations', $this->event);
        $this->resetRegistrationForm();
        $this->showAddRegistrationModal = true;
    }

    public function addRegistration(): void
    {
        $this->authorize('manageRegistrations', $this->event);

        $data = [
            'event_id' => $this->event->id,
            'branch_id' => $this->branch->id,
            'status' => RegistrationStatus::Registered,
            'registered_at' => now(),
            'requires_payment' => $this->event->is_paid,
            'price_paid' => $this->event->is_paid ? $this->event->price : null,
            'is_paid' => ! $this->event->is_paid,
        ];

        if ($this->registrationType === 'member') {
            $this->validate(['member_id' => 'required|exists:members,id']);
            $data['member_id'] = $this->member_id;
        } elseif ($this->registrationType === 'visitor') {
            $this->validate(['visitor_id' => 'required|exists:visitors,id']);
            $data['visitor_id'] = $this->visitor_id;
        } else {
            $this->validate([
                'guest_name' => 'required|string|max:150',
                'guest_email' => 'required|email|max:150',
                'guest_phone' => 'nullable|string|max:30',
            ]);
            $data['guest_name'] = $this->guest_name;
            $data['guest_email'] = $this->guest_email;
            $data['guest_phone'] = $this->guest_phone;
        }

        $registration = EventRegistration::create($data);
        $registration->generateTicketNumber();

        $this->showAddRegistrationModal = false;
        $this->resetRegistrationForm();
        $this->dispatch('registration-added');
    }

    public function checkIn(EventRegistration $registration): void
    {
        $this->authorize('checkIn', $this->event);
        $registration->markAsAttended(CheckInMethod::Manual);
        $this->dispatch('attendee-checked-in');
    }

    public function checkOut(EventRegistration $registration): void
    {
        $this->authorize('checkIn', $this->event);
        $registration->markAsCheckedOut();
        $this->dispatch('attendee-checked-out');
    }

    public function markAsNoShow(EventRegistration $registration): void
    {
        $this->authorize('manageRegistrations', $this->event);
        $registration->markAsNoShow();
        $this->dispatch('registration-updated');
    }

    public function confirmCancel(EventRegistration $registration): void
    {
        $this->authorize('cancel', $registration);
        $this->cancellingRegistration = $registration;
        $this->showCancelRegistrationModal = true;
    }

    public function cancelRegistration(): void
    {
        $this->authorize('cancel', $this->cancellingRegistration);
        $this->cancellingRegistration->markAsCancelled(auth()->user());
        $this->showCancelRegistrationModal = false;
        $this->cancellingRegistration = null;
        $this->dispatch('registration-cancelled');
    }

    public function cancelCancelModal(): void
    {
        $this->showCancelRegistrationModal = false;
        $this->cancellingRegistration = null;
    }

    public function cancelAddModal(): void
    {
        $this->showAddRegistrationModal = false;
        $this->resetRegistrationForm();
    }

    private function resetRegistrationForm(): void
    {
        $this->reset(['member_id', 'visitor_id', 'guest_name', 'guest_email', 'guest_phone']);
        $this->registrationType = 'member';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.events.event-show');
    }
}
