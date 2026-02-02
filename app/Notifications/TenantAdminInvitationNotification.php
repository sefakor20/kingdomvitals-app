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

    public ?string $logoUrl;

    public ?string $appName;

    public function __construct(
        public Tenant $tenant,
        public string $setupUrl,
        ?string $logoUrl = null,
        ?string $appName = null
    ) {
        $this->logoUrl = $logoUrl ?? $tenant->getLogoUrl('medium');
        $this->appName = $appName ?? $tenant->name ?? config('app.name');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Welcome to :tenant - Set Up Your Account', ['tenant' => $this->tenant->name]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('You have been invited to manage :tenant on Kingdom Vitals.', [
                'tenant' => $this->tenant->name,
            ]))
            ->line(__('Click the button below to set your password and get started.'))
            ->action(__('Set Up Your Account'), $this->setupUrl)
            ->line(__('This link will expire in 60 minutes.'))
            ->line(__('If you did not expect this invitation, you can ignore this email.'));

        $message->viewData = [
            'logoUrl' => $this->logoUrl,
            'appName' => $this->appName,
        ];

        return $message;
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
