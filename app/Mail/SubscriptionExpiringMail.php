<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.billing.from.address'),
                config('mail.billing.from.name'),
            ),
            replyTo: [new Address(
                config('mail.billing.reply_to.address'),
                config('mail.billing.reply_to.name'),
            )],
            subject: 'Your Kingdom Vitals Access Expires in 3 Days',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.expiring',
            with: [
                'tenant' => $this->tenant,
                'endsAt' => $this->tenant->subscription_ends_at,
                'daysRemaining' => $this->tenant->subscriptionDaysRemaining(),
            ],
        );
    }
}
