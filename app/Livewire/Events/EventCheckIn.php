<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Enums\CheckInMethod;
use App\Enums\RegistrationStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EventCheckIn extends Component
{
    public Branch $branch;

    public Event $event;

    public string $search = '';

    public string $statusFilter = '';

    public string $activeTab = 'search';

    public bool $isScanning = false;

    public ?string $qrError = null;

    public function mount(Branch $branch, Event $event): void
    {
        $this->authorize('checkIn', $event);
        $this->branch = $branch;
        $this->event = $event;
    }

    #[Computed]
    public function registrations(): Collection
    {
        $query = $this->event->registrations()
            ->with(['member:id,first_name,last_name,email,phone', 'visitor:id,first_name,last_name,email,phone']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->whereHas('member', function ($mq) use ($search): void {
                    $mq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('visitor', function ($vq) use ($search): void {
                        $vq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        if ($this->statusFilter === 'pending') {
            $query->where('status', RegistrationStatus::Registered);
        } elseif ($this->statusFilter === 'checked_in') {
            $query->where('status', RegistrationStatus::Attended);
        }

        return $query->orderBy('registered_at')->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => $this->event->registrations()
                ->whereNotIn('status', [RegistrationStatus::Cancelled->value])
                ->count(),
            'checked_in' => $this->event->registrations()
                ->where('status', RegistrationStatus::Attended->value)
                ->count(),
            'pending' => $this->event->registrations()
                ->where('status', RegistrationStatus::Registered->value)
                ->count(),
        ];
    }

    public function checkIn(EventRegistration $registration): void
    {
        $this->authorize('checkIn', $this->event);

        if ($registration->status !== RegistrationStatus::Registered) {
            return;
        }

        $registration->markAsAttended(CheckInMethod::Manual);
        $this->dispatch('checked-in', name: $registration->attendee_name);
    }

    public function checkOut(EventRegistration $registration): void
    {
        $this->authorize('checkIn', $this->event);

        if (! $registration->is_checked_in) {
            return;
        }

        $registration->markAsCheckedOut();
        $this->dispatch('checked-out', name: $registration->attendee_name);
    }

    public function undoCheckIn(EventRegistration $registration): void
    {
        $this->authorize('checkIn', $this->event);

        $registration->update([
            'status' => RegistrationStatus::Registered,
            'check_in_time' => null,
            'check_in_method' => null,
        ]);

        $this->dispatch('check-in-undone');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->qrError = null;

        if ($tab !== 'qr') {
            $this->isScanning = false;
        }
    }

    public function startScanning(): void
    {
        $this->isScanning = true;
        $this->qrError = null;
    }

    public function stopScanning(): void
    {
        $this->isScanning = false;
    }

    #[On('qr-scanned')]
    public function checkInByQr(string $code): void
    {
        $this->authorize('checkIn', $this->event);
        $this->qrError = null;

        $registration = $this->event->registrations()
            ->where('ticket_number', $code)
            ->first();

        if (! $registration) {
            $this->qrError = __('Ticket not found: :code', ['code' => $code]);
            $this->dispatch('qr-error');

            return;
        }

        if ($registration->status !== RegistrationStatus::Registered) {
            $this->qrError = __(':name is already checked in.', ['name' => $registration->attendee_name]);
            $this->dispatch('already-checked-in');

            return;
        }

        $registration->markAsAttended(CheckInMethod::Qr);
        $this->stopScanning();
        $this->dispatch('checked-in', name: $registration->attendee_name);
    }

    public function render(): View
    {
        return view('livewire.events.event-check-in');
    }
}
