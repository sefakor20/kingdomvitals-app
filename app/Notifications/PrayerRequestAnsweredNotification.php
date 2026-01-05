<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\PrayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrayerRequestAnsweredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PrayerRequest $prayerRequest
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
        $mail = (new MailMessage)
            ->subject("Prayer Answered: {$this->prayerRequest->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('Great news! A prayer request has been marked as answered.')
            ->line("**Title:** {$this->prayerRequest->title}")
            ->when($this->prayerRequest->member, fn ($m) => $m->line("**Submitted by:** {$this->prayerRequest->member->fullName()}"))
            ->when($this->prayerRequest->answered_at, fn ($m) => $m->line("**Answered on:** {$this->prayerRequest->answered_at->format('M d, Y')}"));

        if ($this->prayerRequest->answer_details) {
            $mail->line('**Testimony:**')
                ->line($this->prayerRequest->answer_details);
        }

        return $mail
            ->action('View Prayer Request', url("/branches/{$this->prayerRequest->branch_id}/prayer-requests/{$this->prayerRequest->id}"))
            ->line('Praise God for this answered prayer!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'prayer_request_id' => $this->prayerRequest->id,
            'title' => $this->prayerRequest->title,
            'answered_at' => $this->prayerRequest->answered_at?->toISOString(),
            'member_name' => $this->prayerRequest->member?->fullName(),
            'message' => "Prayer answered: {$this->prayerRequest->title}",
        ];
    }
}
