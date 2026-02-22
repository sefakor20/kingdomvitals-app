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

class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration
    ) {}

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
            ],
        );
    }
}
