<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public string $announcementId,
        public string $recipientId
    ) {}

    public function handle(): void
    {
        $recipient = AnnouncementRecipient::find($this->recipientId);
        if (! $recipient) {
            Log::error('SendAnnouncementJob: Recipient not found', ['recipientId' => $this->recipientId]);

            return;
        }

        $announcement = Announcement::find($this->announcementId);
        if (! $announcement) {
            Log::error('SendAnnouncementJob: Announcement not found', ['announcementId' => $this->announcementId]);
            $recipient->markAsFailed('Announcement not found');

            return;
        }

        $tenant = Tenant::find($recipient->tenant_id);
        if (! $tenant) {
            Log::error('SendAnnouncementJob: Tenant not found', ['tenantId' => $recipient->tenant_id]);
            $recipient->markAsFailed('Tenant not found');
            $announcement->increment('failed_count');

            return;
        }

        try {
            Mail::to($recipient->email)->send(new AnnouncementMail($announcement, $tenant));

            $recipient->update([
                'delivery_status' => DeliveryStatus::Sent,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            $announcement->increment('successful_count');

            Log::info('SendAnnouncementJob: Email sent successfully', [
                'announcementId' => $this->announcementId,
                'recipientId' => $this->recipientId,
                'email' => $recipient->email,
            ]);
        } catch (\Exception $e) {
            $recipient->update([
                'delivery_status' => DeliveryStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            $announcement->increment('failed_count');

            Log::error('SendAnnouncementJob: Failed to send email', [
                'announcementId' => $this->announcementId,
                'recipientId' => $this->recipientId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAnnouncementJob failed permanently', [
            'announcementId' => $this->announcementId,
            'recipientId' => $this->recipientId,
            'exception' => $exception->getMessage(),
        ]);

        $recipient = AnnouncementRecipient::find($this->recipientId);
        if ($recipient && $recipient->delivery_status !== DeliveryStatus::Failed) {
            $recipient->markAsFailed($exception->getMessage());

            $announcement = Announcement::find($this->announcementId);
            if ($announcement) {
                $announcement->increment('failed_count');
            }
        }
    }
}
