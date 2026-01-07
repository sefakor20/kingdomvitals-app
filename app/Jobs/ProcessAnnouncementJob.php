<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetAudience;
use App\Enums\DeliveryStatus;
use App\Enums\TenantStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessAnnouncementJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public string $announcementId
    ) {}

    public function handle(): void
    {
        $announcement = Announcement::find($this->announcementId);
        if (! $announcement) {
            Log::error('ProcessAnnouncementJob: Announcement not found', ['announcementId' => $this->announcementId]);

            return;
        }

        if (! $announcement->canBeSent()) {
            Log::warning('ProcessAnnouncementJob: Announcement cannot be sent', [
                'announcementId' => $this->announcementId,
                'status' => $announcement->status->value,
            ]);

            return;
        }

        $announcement->update(['status' => AnnouncementStatus::Sending]);

        $tenants = $this->getTargetTenants($announcement);

        $recipientCount = 0;
        foreach ($tenants as $tenant) {
            if (empty($tenant->contact_email)) {
                continue;
            }

            $recipient = AnnouncementRecipient::create([
                'announcement_id' => $announcement->id,
                'tenant_id' => $tenant->id,
                'email' => $tenant->contact_email,
                'delivery_status' => DeliveryStatus::Pending,
            ]);

            $delay = rand(1, 10);
            SendAnnouncementJob::dispatch($announcement->id, $recipient->id)
                ->delay(now()->addSeconds($delay));

            $recipientCount++;
        }

        $announcement->update(['total_recipients' => $recipientCount]);

        if ($recipientCount === 0) {
            $announcement->update([
                'status' => AnnouncementStatus::Sent,
                'sent_at' => now(),
            ]);

            return;
        }

        CheckAnnouncementCompletionJob::dispatch($announcement->id)
            ->delay(now()->addMinutes(5));

        Log::info('ProcessAnnouncementJob: Dispatched email jobs', [
            'announcementId' => $this->announcementId,
            'recipientCount' => $recipientCount,
        ]);
    }

    protected function getTargetTenants(Announcement $announcement): Collection
    {
        $query = Tenant::query();

        return match ($announcement->target_audience) {
            AnnouncementTargetAudience::All => $query->get(),
            AnnouncementTargetAudience::Active => $query->where('status', TenantStatus::Active)->get(),
            AnnouncementTargetAudience::Trial => $query->where('status', TenantStatus::Trial)->get(),
            AnnouncementTargetAudience::Suspended => $query->where('status', TenantStatus::Suspended)->get(),
            AnnouncementTargetAudience::Specific => $query->whereIn('id', $announcement->specific_tenant_ids ?? [])->get(),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAnnouncementJob failed', [
            'announcementId' => $this->announcementId,
            'exception' => $exception->getMessage(),
        ]);

        $announcement = Announcement::find($this->announcementId);
        if ($announcement) {
            $announcement->update(['status' => AnnouncementStatus::Failed]);
        }
    }
}
