<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Announcement;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public Tenant $tenant
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->announcement->priority->emailSubjectPrefix();

        return new Envelope(
            subject: $prefix.$this->announcement->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.announcement',
            with: [
                'announcement' => $this->announcement,
                'tenant' => $this->tenant,
                'priority' => $this->announcement->priority,
            ],
        );
    }
}
