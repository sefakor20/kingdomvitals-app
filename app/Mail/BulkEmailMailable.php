<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $emailBody,
        public EmailLog $emailLog,
        public ?Branch $branch = null
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = $this->branch?->getSetting('email_sender_address') ?? config('mail.from.address');
        $fromName = $this->branch?->getSetting('email_sender_name') ?? $this->branch?->name ?? config('mail.from.name');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bulk-email',
            with: [
                'body' => $this->addTrackingToBody($this->emailBody),
                'branch' => $this->branch,
                'emailLog' => $this->emailLog,
            ],
        );
    }

    protected function addTrackingToBody(string $body): string
    {
        $trackingPixelUrl = route('email.track.pixel', ['emailLog' => $this->emailLog->id]);
        $trackingPixel = '<img src="'.$trackingPixelUrl.'" width="1" height="1" style="display:none;" alt="" />';

        $body = $this->wrapLinksForTracking($body);

        return $body.$trackingPixel;
    }

    protected function wrapLinksForTracking(string $body): string
    {
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) {
                $beforeHref = $matches[1];
                $originalUrl = $matches[2];
                $afterHref = $matches[3];

                if (str_contains($originalUrl, 'email/track/')) {
                    return $matches[0];
                }

                $trackingUrl = route('email.track.click', [
                    'emailLog' => $this->emailLog->id,
                    'url' => base64_encode($originalUrl),
                ]);

                return '<a '.$beforeHref.'href="'.$trackingUrl.'"'.$afterHref.'>';
            },
            $body
        );
    }
}
