<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use App\Services\PlanAccessService;
use App\Services\TextTangoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendVisitorFollowUpSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $visitorId,
        public string $branchId,
        public string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $visitor = Visitor::find($this->visitorId);

        if (! $visitor) {
            Log::warning('SendVisitorFollowUpSmsJob: Visitor not found', ['visitor_id' => $this->visitorId]);

            return;
        }

        // Check if visitor has a phone number
        if (empty($visitor->phone)) {
            Log::info('SendVisitorFollowUpSmsJob: Visitor has no phone number', ['visitor_id' => $visitor->id]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('SendVisitorFollowUpSmsJob: Branch not found', [
                'visitor_id' => $visitor->id,
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        // Check if branch has SMS configured
        if (! $branch->hasSmsConfigured()) {
            Log::info('SendVisitorFollowUpSmsJob: SMS not configured for branch', ['branch_id' => $branch->id]);

            return;
        }

        // Check SMS quota before sending
        $planAccess = app(PlanAccessService::class);
        if (! $planAccess->canSendSms(1)) {
            Log::warning('SendVisitorFollowUpSmsJob: SMS quota exceeded', ['visitor_id' => $visitor->id]);

            return;
        }

        // Get TextTango service for this branch
        $service = TextTangoService::forBranch($branch);

        if (! $service->isConfigured()) {
            Log::error('SendVisitorFollowUpSmsJob: SMS service not configured', ['branch_id' => $branch->id]);

            return;
        }

        // Send SMS
        $result = $service->sendBulkSms([$visitor->phone], $this->message);

        // Log the SMS
        SmsLog::create([
            'branch_id' => $branch->id,
            'visitor_id' => $visitor->id,
            'phone_number' => $visitor->phone,
            'message' => $this->message,
            'message_type' => SmsType::FollowUp,
            'status' => $result['success'] ? SmsStatus::Sent : SmsStatus::Failed,
            'provider' => 'texttango',
            'provider_message_id' => $result['tracking_id'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['error'] ?? null,
        ]);

        if ($result['success']) {
            // Invalidate SMS count cache for quota tracking
            $planAccess->invalidateCountCache('sms');

            Log::info('SendVisitorFollowUpSmsJob: Follow-up SMS sent', [
                'visitor_id' => $visitor->id,
                'phone' => $visitor->phone,
            ]);
        } else {
            Log::error('SendVisitorFollowUpSmsJob: Failed to send follow-up SMS', [
                'visitor_id' => $visitor->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendVisitorFollowUpSmsJob failed', [
            'visitor_id' => $this->visitorId,
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
