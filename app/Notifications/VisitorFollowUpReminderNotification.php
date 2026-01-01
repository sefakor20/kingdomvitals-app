<?php

namespace App\Notifications;

use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorFollowUpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitorFollowUp $followUp
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visitor = $this->followUp->visitor;
        $type = ucfirst($this->followUp->type->value);
        $scheduledAt = $this->followUp->scheduled_at->format('M d, Y \a\t g:i A');

        return (new MailMessage)
            ->subject("Follow-up Reminder: {$visitor->fullName()}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have a scheduled follow-up for visitor **{$visitor->fullName()}**.")
            ->line("**Type:** {$type}")
            ->line("**Scheduled:** {$scheduledAt}")
            ->when($visitor->phone, fn ($mail) => $mail->line("**Phone:** {$visitor->phone}"))
            ->when($visitor->email, fn ($mail) => $mail->line("**Email:** {$visitor->email}"))
            ->when($this->followUp->notes, fn ($mail) => $mail->line("**Notes:** {$this->followUp->notes}"))
            ->action('View Visitor', url("/visitors/{$visitor->branch_id}/{$visitor->id}"))
            ->line('Please complete this follow-up at your earliest convenience.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $visitor = $this->followUp->visitor;

        return [
            'follow_up_id' => $this->followUp->id,
            'visitor_id' => $visitor->id,
            'visitor_name' => $visitor->fullName(),
            'type' => $this->followUp->type->value,
            'scheduled_at' => $this->followUp->scheduled_at->toISOString(),
            'message' => "Follow-up reminder for {$visitor->fullName()}",
        ];
    }
}
