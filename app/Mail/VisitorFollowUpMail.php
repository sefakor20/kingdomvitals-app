<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitorFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Visitor $visitor,
        public string $messageBody,
        public Branch $branch,
        public ?string $emailSubject = null
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = $this->branch->getSetting('email_sender_address') ?? config('mail.from.address');
        $fromName = $this->branch->getSetting('email_sender_name') ?? $this->branch->name ?? config('mail.from.name');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->emailSubject ?? __('A message from :branch', ['branch' => $this->branch->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitor-follow-up',
            with: [
                'visitor' => $this->visitor,
                'messageBody' => $this->messageBody,
                'branch' => $this->branch,
            ],
        );
    }
}
