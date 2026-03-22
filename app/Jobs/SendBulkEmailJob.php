<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use App\Services\BulkEmailService;
use App\Services\PlanAccessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBulkEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  array<string>  $emailLogIds  Array of EmailLog IDs
     */
    public function __construct(
        public array $emailLogIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $firstEmailLog = EmailLog::find($this->emailLogIds[0] ?? null);
        if (! $firstEmailLog) {
            Log::error('SendBulkEmailJob: Could not find EmailLog', ['emailLogIds' => $this->emailLogIds]);

            return;
        }

        $branch = Branch::find($firstEmailLog->branch_id);
        if (! $branch) {
            Log::error('SendBulkEmailJob: Could not find Branch', ['branch_id' => $firstEmailLog->branch_id]);
            $this->markAllAsFailed('Branch not found');

            return;
        }

        // Check email quota before sending (secondary safety check)
        $planAccess = app(PlanAccessService::class);
        $recipientCount = count($this->emailLogIds);
        if (! $planAccess->canSendEmail($recipientCount)) {
            Log::warning('SendBulkEmailJob: Email quota exceeded', [
                'recipients' => $recipientCount,
                'emailLogIds' => $this->emailLogIds,
            ]);
            $this->markAllAsFailed('Email quota exceeded');

            return;
        }

        // Get the email service for this branch
        $service = BulkEmailService::forBranch($branch);

        // Send each email individually (emails are personalized per recipient)
        $successCount = 0;
        $failCount = 0;

        foreach ($this->emailLogIds as $emailLogId) {
            $emailLog = EmailLog::find($emailLogId);
            if (! $emailLog) {
                continue;
            }

            if ($service->send($emailLog)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // Invalidate email count cache for quota tracking
        $planAccess->invalidateCountCache('email');

        Log::info('SendBulkEmailJob: Bulk email sending completed', [
            'success' => $successCount,
            'failed' => $failCount,
            'total' => count($this->emailLogIds),
        ]);
    }

    /**
     * Mark all EmailLog records as failed.
     */
    protected function markAllAsFailed(string $errorMessage): void
    {
        EmailLog::whereIn('id', $this->emailLogIds)->update([
            'status' => EmailStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkEmailJob failed', [
            'exception' => $exception->getMessage(),
            'emailLogIds' => $this->emailLogIds,
        ]);

        $this->markAllAsFailed($exception->getMessage());
    }
}
