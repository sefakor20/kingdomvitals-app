<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWelcomeSmsJob implements ShouldQueue
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
        public string $memberId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $member = Member::find($this->memberId);

        if (! $member) {
            Log::warning('SendWelcomeSmsJob: Member not found', ['member_id' => $this->memberId]);

            return;
        }

        // Check if member has a phone number
        if (empty($member->phone)) {
            Log::info('SendWelcomeSmsJob: Member has no phone number', ['member_id' => $member->id]);

            return;
        }

        // Check if member is active
        if ($member->status->value !== 'active') {
            Log::info('SendWelcomeSmsJob: Member is not active', [
                'member_id' => $member->id,
                'status' => $member->status->value,
            ]);

            return;
        }

        $branch = Branch::find($member->primary_branch_id);

        if (! $branch) {
            Log::warning('SendWelcomeSmsJob: Branch not found', [
                'member_id' => $member->id,
                'branch_id' => $member->primary_branch_id,
            ]);

            return;
        }

        // Check if branch has SMS configured
        if (! $branch->hasSmsConfigured()) {
            Log::info('SendWelcomeSmsJob: SMS not configured for branch', ['branch_id' => $branch->id]);

            return;
        }

        // Check if auto welcome SMS is enabled
        if (! $branch->getSetting('auto_welcome_sms', false)) {
            Log::info('SendWelcomeSmsJob: Auto welcome SMS disabled for branch', ['branch_id' => $branch->id]);

            return;
        }

        // Check if welcome SMS already sent (prevent duplicates)
        $alreadySent = SmsLog::where('member_id', $member->id)
            ->where('message_type', SmsType::Welcome)
            ->exists();

        if ($alreadySent) {
            Log::info('SendWelcomeSmsJob: Welcome SMS already sent', ['member_id' => $member->id]);

            return;
        }

        // Get welcome message
        $message = $this->getWelcomeMessage($branch);
        $personalizedMessage = $this->personalizeMessage($message, $member, $branch);

        // Get TextTango service for this branch
        $service = TextTangoService::forBranch($branch);

        if (! $service->isConfigured()) {
            Log::error('SendWelcomeSmsJob: SMS service not configured', ['branch_id' => $branch->id]);

            return;
        }

        // Send SMS
        $result = $service->sendBulkSms([$member->phone], $personalizedMessage);

        // Log the SMS
        SmsLog::create([
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'phone_number' => $member->phone,
            'message' => $personalizedMessage,
            'message_type' => SmsType::Welcome,
            'status' => $result['success'] ? SmsStatus::Sent : SmsStatus::Failed,
            'provider' => 'texttango',
            'provider_message_id' => $result['tracking_id'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['error'] ?? null,
        ]);

        if ($result['success']) {
            Log::info('SendWelcomeSmsJob: Welcome SMS sent', [
                'member_id' => $member->id,
                'phone' => $member->phone,
            ]);
        } else {
            Log::error('SendWelcomeSmsJob: Failed to send welcome SMS', [
                'member_id' => $member->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Get the welcome message template.
     */
    protected function getWelcomeMessage(Branch $branch): string
    {
        // Check if branch has a specific welcome template configured
        $templateId = $branch->getSetting('welcome_template_id');

        if ($templateId) {
            $template = SmsTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template->body;
            }
        }

        // Try to find any active welcome template for this branch
        $template = SmsTemplate::where('branch_id', $branch->id)
            ->where('type', SmsType::Welcome)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->body;
        }

        // Default welcome message
        return "Welcome to {branch_name}, {first_name}! We're excited to have you as part of our family. God bless you!";
    }

    /**
     * Personalize the message with member and branch details.
     */
    protected function personalizeMessage(string $message, Member $member, Branch $branch): string
    {
        $replacements = [
            '{first_name}' => $member->first_name,
            '{last_name}' => $member->last_name,
            '{full_name}' => $member->fullName(),
            '{branch_name}' => $branch->name,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWelcomeSmsJob failed', [
            'member_id' => $this->memberId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
