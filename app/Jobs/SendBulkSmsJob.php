<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Services\TextTangoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBulkSmsJob implements ShouldQueue
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
     * @param  array<string>  $smsLogIds  Array of SmsLog IDs
     * @param  array<string>  $phoneNumbers  Array of phone numbers
     * @param  string  $message  The SMS message content
     * @param  string|null  $scheduledAt  Datetime for scheduled SMS (optional)
     */
    public function __construct(
        public array $smsLogIds,
        public array $phoneNumbers,
        public string $message,
        public ?string $scheduledAt = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the first SmsLog to determine the branch
        $firstSmsLog = SmsLog::find($this->smsLogIds[0] ?? null);
        if (! $firstSmsLog) {
            Log::error('SendBulkSmsJob: Could not find SmsLog', ['smsLogIds' => $this->smsLogIds]);

            return;
        }

        $branch = Branch::find($firstSmsLog->branch_id);
        if (! $branch) {
            Log::error('SendBulkSmsJob: Could not find Branch', ['branch_id' => $firstSmsLog->branch_id]);
            $this->markAllAsFailed('Branch not found');

            return;
        }

        // Get the TextTango service for this branch
        $service = TextTangoService::forBranch($branch);

        if (! $service->isConfigured()) {
            Log::error('SendBulkSmsJob: SMS service not configured for branch', ['branch_id' => $branch->id]);
            $this->markAllAsFailed('SMS service not configured');

            return;
        }

        // Send the bulk SMS
        $isScheduled = ! empty($this->scheduledAt);
        $result = $service->sendBulkSms(
            $this->phoneNumbers,
            $this->message,
            null,
            $isScheduled,
            $this->scheduledAt
        );

        if ($result['success']) {
            // Update all SmsLog records with success status
            $trackingId = $result['tracking_id'] ?? null;

            SmsLog::whereIn('id', $this->smsLogIds)->update([
                'status' => SmsStatus::Sent,
                'provider_message_id' => $trackingId,
                'sent_at' => now(),
            ]);

            Log::info('SendBulkSmsJob: Bulk SMS sent successfully', [
                'tracking_id' => $trackingId,
                'recipients' => count($this->phoneNumbers),
            ]);
        } else {
            // Mark all as failed
            $this->markAllAsFailed($result['error'] ?? 'Unknown error');

            Log::error('SendBulkSmsJob: Failed to send bulk SMS', [
                'error' => $result['error'] ?? 'Unknown error',
                'recipients' => count($this->phoneNumbers),
            ]);
        }
    }

    /**
     * Mark all SmsLog records as failed.
     */
    protected function markAllAsFailed(string $errorMessage): void
    {
        SmsLog::whereIn('id', $this->smsLogIds)->update([
            'status' => SmsStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkSmsJob failed', [
            'exception' => $exception->getMessage(),
            'smsLogIds' => $this->smsLogIds,
        ]);

        $this->markAllAsFailed($exception->getMessage());
    }
}
