<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\PrayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrayerRequestSubmittedNotification extends Notification implements ShouldQueue
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
        $member = $this->prayerRequest->member;
        $category = ucfirst(str_replace('_', ' ', $this->prayerRequest->category->value));
        $privacy = ucfirst(str_replace('_', ' ', $this->prayerRequest->privacy->value));

        return (new MailMessage)
            ->subject("New Prayer Request: {$this->prayerRequest->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('A new prayer request has been submitted that requires your attention.')
            ->line("**Title:** {$this->prayerRequest->title}")
            ->line("**Category:** {$category}")
            ->line("**Privacy:** {$privacy}")
            ->when($member, fn ($mail) => $mail->line("**Submitted by:** {$member->fullName()}"))
            ->when($this->prayerRequest->cluster, fn ($mail) => $mail->line("**Cluster:** {$this->prayerRequest->cluster->name}"))
            ->line('**Request:**')
            ->line($this->prayerRequest->description)
            ->action('View Prayer Request', url("/branches/{$this->prayerRequest->branch_id}/prayer-requests/{$this->prayerRequest->id}"))
            ->line('Please keep this person in your prayers.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'prayer_request_id' => $this->prayerRequest->id,
            'title' => $this->prayerRequest->title,
            'category' => $this->prayerRequest->category->value,
            'member_name' => $this->prayerRequest->member?->fullName(),
            'message' => "New prayer request: {$this->prayerRequest->title}",
        ];
    }
}
