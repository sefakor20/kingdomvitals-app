<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnnouncementStatus;
use App\Enums\DeliveryStatus;
use App\Models\Announcement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckAnnouncementCompletionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 30;

    public function __construct(
        public string $announcementId
    ) {}

    public function handle(): void
    {
        $announcement = Announcement::find($this->announcementId);
        if (! $announcement) {
            Log::error('CheckAnnouncementCompletionJob: Announcement not found', [
                'announcementId' => $this->announcementId,
            ]);

            return;
        }

        if ($announcement->status !== AnnouncementStatus::Sending) {
            Log::info('CheckAnnouncementCompletionJob: Announcement no longer sending', [
                'announcementId' => $this->announcementId,
                'status' => $announcement->status->value,
            ]);

            return;
        }

        $pendingCount = $announcement->recipients()
            ->where('delivery_status', DeliveryStatus::Pending)
            ->count();

        if ($pendingCount > 0) {
            Log::info('CheckAnnouncementCompletionJob: Still has pending recipients, re-checking later', [
                'announcementId' => $this->announcementId,
                'pendingCount' => $pendingCount,
            ]);

            self::dispatch($this->announcementId)->delay(now()->addMinutes(2));

            return;
        }

        $status = $announcement->failed_count > 0
            ? AnnouncementStatus::PartiallyFailed
            : AnnouncementStatus::Sent;

        $announcement->update([
            'status' => $status,
            'sent_at' => now(),
        ]);

        Log::info('CheckAnnouncementCompletionJob: Announcement completed', [
            'announcementId' => $this->announcementId,
            'status' => $status->value,
            'successfulCount' => $announcement->successful_count,
            'failedCount' => $announcement->failed_count,
        ]);
    }
}
