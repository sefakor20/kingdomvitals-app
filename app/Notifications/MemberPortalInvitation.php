<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\MemberInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberPortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected ?string $tenantBaseUrl = null;

    public function __construct(
        public MemberInvitation $invitation
    ) {
        $this->resolveTenantBaseUrl();
    }

    /**
     * Resolve the tenant's base URL for invitation links.
     * This ensures URLs work correctly when sent from queue workers.
     */
    protected function resolveTenantBaseUrl(): void
    {
        $tenant = tenancy()->tenant;
        $domain = $tenant?->domains()->first()?->domain;

        if ($domain) {
            $scheme = app()->environment('production') ? 'https' : 'http';
            $this->tenantBaseUrl = "{$scheme}://{$domain}";
        }
    }

    /**
     * Build a URL using the tenant's domain.
     */
    protected function buildUrl(string $path): string
    {
        if ($this->tenantBaseUrl) {
            return "{$this->tenantBaseUrl}{$path}";
        }

        return url($path);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $member = $this->invitation->member;
        $branch = $this->invitation->branch;

        $acceptUrl = $this->buildUrl("/member/invitation/{$this->invitation->token}");

        return (new MailMessage)
            ->subject(__('You\'re Invited to the Member Portal'))
            ->greeting(__('Hello :name!', ['name' => $member->first_name]))
            ->line(__('You\'ve been invited to access the member portal for :branch.', [
                'branch' => $branch->name,
            ]))
            ->line(__('With the member portal, you can:'))
            ->line('• '.__('View your giving history and download statements'))
            ->line('• '.__('Track your attendance'))
            ->line('• '.__('Manage your pledges'))
            ->line('• '.__('View your event registrations'))
            ->line('• '.__('Update your contact information'))
            ->action(__('Accept Invitation'), $acceptUrl)
            ->line(__('This invitation will expire in 7 days.'))
            ->salutation(__('Best regards,')."\n".$branch->name);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'member_id' => $this->invitation->member_id,
            'branch_id' => $this->invitation->branch_id,
        ];
    }
}
