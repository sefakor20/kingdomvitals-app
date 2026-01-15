<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\SmsLog;
use App\Models\User;
use App\Services\PlanAccessService;
use App\Services\TextTangoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPrayerChainSmsJob implements ShouldQueue
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
     */
    public function __construct(
        public PrayerRequest $prayerRequest,
        public ?User $sentBy = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prayerRequest = $this->prayerRequest;
        $branch = $prayerRequest->branch;

        if (! $branch) {
            Log::error('SendPrayerChainSmsJob: Could not find Branch', ['prayer_request_id' => $prayerRequest->id]);

            return;
        }

        // Get cluster members who haven't opted out of SMS
        $members = $this->getEligibleMembers($prayerRequest);

        if ($members->isEmpty()) {
            Log::info('SendPrayerChainSmsJob: No eligible members to notify', ['prayer_request_id' => $prayerRequest->id]);

            return;
        }

        // Check SMS quota before sending (secondary safety check)
        $planAccess = app(PlanAccessService::class);
        $recipientCount = $members->count();
        if (! $planAccess->canSendSms($recipientCount)) {
            Log::warning('SendPrayerChainSmsJob: SMS quota exceeded', [
                'prayer_request_id' => $prayerRequest->id,
                'recipients' => $recipientCount,
            ]);

            return;
        }

        // Get the TextTango service for this branch
        $service = TextTangoService::forBranch($branch);

        if (! $service->isConfigured()) {
            Log::error('SendPrayerChainSmsJob: SMS service not configured for branch', ['branch_id' => $branch->id]);

            return;
        }

        // Prepare the message
        $message = $this->buildMessage($prayerRequest);

        // Create SmsLog records and collect phone numbers
        $smsLogIds = [];
        $phoneNumbers = [];

        foreach ($members as $member) {
            if (! $member->phone) {
                continue;
            }

            $smsLog = SmsLog::create([
                'branch_id' => $branch->id,
                'member_id' => $member->id,
                'phone_number' => $member->phone,
                'message' => $message,
                'message_type' => SmsType::PrayerChain,
                'status' => SmsStatus::Pending,
                'provider' => 'texttango',
                'sent_by' => $this->sentBy?->id,
            ]);

            $smsLogIds[] = $smsLog->id;
            $phoneNumbers[] = $member->phone;
        }

        if ($phoneNumbers === []) {
            Log::info('SendPrayerChainSmsJob: No phone numbers to send to', ['prayer_request_id' => $prayerRequest->id]);

            return;
        }

        // Send the bulk SMS
        $result = $service->sendBulkSms($phoneNumbers, $message);

        if ($result['success']) {
            $trackingId = $result['tracking_id'] ?? null;

            SmsLog::whereIn('id', $smsLogIds)->update([
                'status' => SmsStatus::Sent,
                'provider_message_id' => $trackingId,
                'sent_at' => now(),
            ]);

            // Invalidate SMS count cache for quota tracking
            $planAccess->invalidateCountCache('sms');

            Log::info('SendPrayerChainSmsJob: Prayer chain SMS sent successfully', [
                'prayer_request_id' => $prayerRequest->id,
                'tracking_id' => $trackingId,
                'recipients' => count($phoneNumbers),
            ]);
        } else {
            SmsLog::whereIn('id', $smsLogIds)->update([
                'status' => SmsStatus::Failed,
                'error_message' => $result['error'] ?? 'Unknown error',
            ]);

            Log::error('SendPrayerChainSmsJob: Failed to send prayer chain SMS', [
                'prayer_request_id' => $prayerRequest->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Get members eligible to receive the prayer chain SMS.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Member>
     */
    protected function getEligibleMembers(PrayerRequest $prayerRequest): \Illuminate\Database\Eloquent\Collection
    {
        $query = Member::query()
            ->notOptedOutOfSms()
            ->whereNotNull('phone');

        // If prayer request is assigned to a cluster, only notify that cluster's members
        if ($prayerRequest->cluster_id) {
            $query->whereHas('clusters', function ($q) use ($prayerRequest): void {
                $q->where('clusters.id', $prayerRequest->cluster_id);
            });
        } else {
            // Otherwise, notify all branch members
            $query->where('primary_branch_id', $prayerRequest->branch_id);
        }

        // Exclude the member who submitted the request (they already know about it)
        if ($prayerRequest->member_id) {
            $query->where('id', '!=', $prayerRequest->member_id);
        }

        return $query->get();
    }

    /**
     * Build the SMS message for the prayer chain.
     */
    protected function buildMessage(PrayerRequest $prayerRequest): string
    {
        $category = ucfirst(str_replace('_', ' ', $prayerRequest->category->value));

        $message = "Prayer Request ({$category}): ";

        // Keep message concise for SMS
        $title = $prayerRequest->title;
        if (strlen($title) > 80) {
            $title = substr($title, 0, 77).'...';
        }

        $message .= $title;

        if ($prayerRequest->cluster) {
            $message .= " - {$prayerRequest->cluster->name}";
        }

        return $message;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPrayerChainSmsJob failed', [
            'prayer_request_id' => $this->prayerRequest->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
