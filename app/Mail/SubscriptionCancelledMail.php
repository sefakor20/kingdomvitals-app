<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Kingdom Vitals Subscription Has Been Cancelled',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.cancelled',
            with: [
                'tenant' => $this->tenant,
                'endsAt' => $this->tenant->subscription_ends_at,
                'daysRemaining' => $this->tenant->subscriptionDaysRemaining(),
            ],
        );
    }
}
