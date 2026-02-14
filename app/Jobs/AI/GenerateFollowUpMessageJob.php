<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Enums\FollowUpType;
use App\Models\Tenant\AiGeneratedMessage;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Services\AI\MessageGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFollowUpMessageJob implements ShouldQueue
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
        public string $recipientType,
        public string $recipientId,
        public string $channel = 'sms'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MessageGenerationService $service): ?AiGeneratedMessage
    {
        Log::info('GenerateFollowUpMessageJob: Starting', [
            'recipient_type' => $this->recipientType,
            'recipient_id' => $this->recipientId,
            'channel' => $this->channel,
        ]);

        try {
            $channel = FollowUpType::from($this->channel);

            if ($this->recipientType === 'visitor') {
                $visitor = Visitor::find($this->recipientId);

                if (! $visitor) {
                    Log::warning('GenerateFollowUpMessageJob: Visitor not found', [
                        'visitor_id' => $this->recipientId,
                    ]);

                    return null;
                }

                $message = $service->createVisitorMessage($visitor, $channel);
            } else {
                $member = Member::find($this->recipientId);

                if (! $member) {
                    Log::warning('GenerateFollowUpMessageJob: Member not found', [
                        'member_id' => $this->recipientId,
                    ]);

                    return null;
                }

                $message = $service->createMemberMessage($member, $channel);
            }

            Log::info('GenerateFollowUpMessageJob: Completed', [
                'recipient_type' => $this->recipientType,
                'recipient_id' => $this->recipientId,
                'message_id' => $message->id,
            ]);

            return $message;
        } catch (\Throwable $e) {
            Log::error('GenerateFollowUpMessageJob: Failed', [
                'recipient_type' => $this->recipientType,
                'recipient_id' => $this->recipientId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateFollowUpMessageJob failed', [
            'recipient_type' => $this->recipientType,
            'recipient_id' => $this->recipientId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
