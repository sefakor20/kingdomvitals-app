<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $ticketDownloadUrl = '';

    public string $eventDetailsUrl;

    public function __construct(
        public EventRegistration $registration
    ) {
        // Pre-generate URLs with tenant domain before queuing
        // This ensures the URLs use the correct tenant domain, not APP_URL
        if ($registration->ticket_number) {
            $this->ticketDownloadUrl = URL::signedRoute('events.public.ticket.download', [
                $registration->branch,
                $registration->event,
                $registration,
            ]);
        }

        $this->eventDetailsUrl = route('events.public.details', [
            $registration->branch,
            $registration->event,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Reminder: :event is coming up!', [
                'event' => $this->registration->event->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-reminder',
            with: [
                'registration' => $this->registration,
                'event' => $this->registration->event,
                'branch' => $this->registration->branch,
                'attendeeName' => $this->registration->attendee_name,
                'ticketDownloadUrl' => $this->ticketDownloadUrl,
                'eventDetailsUrl' => $this->eventDetailsUrl,
            ],
        );
    }
}
