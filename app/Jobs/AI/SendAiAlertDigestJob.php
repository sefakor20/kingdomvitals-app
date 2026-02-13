<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Branch;
use App\Notifications\AI\DailyAiAlertDigestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendAiAlertDigestJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param  int  $hoursBack  Number of hours to look back for alerts (default: 24)
     */
    public function __construct(
        public string $branchId,
        public int $hoursBack = 24
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendAiAlertDigestJob: Starting', [
            'branch_id' => $this->branchId,
            'hours_back' => $this->hoursBack,
        ]);

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('SendAiAlertDigestJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        // Get alerts from the specified time period
        $alerts = AiAlert::forBranch($this->branchId)
            ->where('created_at', '>=', now()->subHours($this->hoursBack))
            ->orderBySeverity()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($alerts->isEmpty()) {
            Log::info('SendAiAlertDigestJob: No alerts to send', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        // Get branch admins/pastors to notify
        $notifiables = $branch->users()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['pastor', 'admin']))
            ->get();

        if ($notifiables->isEmpty()) {
            Log::info('SendAiAlertDigestJob: No notifiable users found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        try {
            Notification::send(
                $notifiables,
                new DailyAiAlertDigestNotification(
                    $alerts,
                    $this->branchId,
                    $branch->name
                )
            );

            Log::info('SendAiAlertDigestJob: Digest sent', [
                'branch_id' => $this->branchId,
                'alert_count' => $alerts->count(),
                'recipients' => $notifiables->count(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendAiAlertDigestJob: Failed to send digest', [
                'branch_id' => $this->branchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAiAlertDigestJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
