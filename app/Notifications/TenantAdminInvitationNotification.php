<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantAdminInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $setupUrl
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Welcome to :tenant - Set Up Your Account', ['tenant' => $this->tenant->name]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('You have been invited to manage :tenant on Kingdom Vitals.', [
                'tenant' => $this->tenant->name,
            ]))
            ->line(__('Click the button below to set your password and get started.'))
            ->action(__('Set Up Your Account'), $this->setupUrl)
            ->line(__('This link will expire in 60 minutes.'))
            ->line(__('If you did not expect this invitation, you can ignore this email.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ];
    }
}
