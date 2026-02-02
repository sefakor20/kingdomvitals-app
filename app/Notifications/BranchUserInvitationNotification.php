<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\BranchUserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchUserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?string $logoUrl;

    public ?string $appName;

    public function __construct(
        public BranchUserInvitation $invitation,
        public string $acceptUrl,
        ?string $logoUrl = null,
        ?string $appName = null
    ) {
        $this->logoUrl = $logoUrl;
        $this->appName = $appName;
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
        $roleName = ucfirst($this->invitation->role->value);
        $branchName = $this->invitation->branch->name;
        $inviterName = $this->invitation->invitedBy?->name ?? 'A team member';

        $message = (new MailMessage)
            ->subject(__("You're invited to join :branch", ['branch' => $branchName]))
            ->greeting(__('Hello!'))
            ->line(__(':inviter has invited you to join :branch as a :role.', [
                'inviter' => $inviterName,
                'branch' => $branchName,
                'role' => $roleName,
            ]))
            ->action(__('Accept Invitation'), $this->acceptUrl)
            ->line(__('This invitation will expire on :date.', [
                'date' => $this->invitation->expires_at->format('F j, Y'),
            ]))
            ->line(__('If you did not expect this invitation, you can safely ignore this email.'));

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
            'invitation_id' => $this->invitation->id,
            'branch_id' => $this->invitation->branch_id,
            'branch_name' => $this->invitation->branch->name,
            'role' => $this->invitation->role->value,
            'invited_by' => $this->invitation->invited_by,
        ];
    }
}
