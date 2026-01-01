<?php

namespace App\Notifications;

use App\Models\Tenant\Branch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitedToBranchNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Branch $branch,
        public string $role,
        public User $invitedBy
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
        $roleName = ucfirst($this->role);

        return (new MailMessage)
            ->subject(__("You've been added to :branch", ['branch' => $this->branch->name]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('You have been added to :branch as a :role.', [
                'branch' => $this->branch->name,
                'role' => $roleName,
            ]))
            ->line(__('Added by: :name', ['name' => $this->invitedBy->name]))
            ->action(__('View Dashboard'), url('/dashboard'))
            ->line(__('Welcome to the team!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branch->id,
            'branch_name' => $this->branch->name,
            'role' => $this->role,
            'invited_by' => $this->invitedBy->id,
        ];
    }
}
