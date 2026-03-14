<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Enums\ChatbotChannel;
use App\Models\Tenant\Branch;
use App\Services\AI\ChatbotService;
use App\Services\TextTangoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessChatbotMessageJob implements ShouldQueue
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
        public string $branchId,
        public string $phoneNumber,
        public string $message,
        public ChatbotChannel $channel
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ChatbotService $chatbotService, TextTangoService $smsService): void
    {
        if (! $chatbotService->isEnabled()) {
            Log::info('ProcessChatbotMessageJob: Chatbot feature disabled, skipping', [
                'branch_id' => $this->branchId,
                'phone' => $this->phoneNumber,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('ProcessChatbotMessageJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('ProcessChatbotMessageJob: Processing message', [
            'branch_id' => $this->branchId,
            'phone' => $this->phoneNumber,
            'channel' => $this->channel->value,
            'message_length' => strlen($this->message),
        ]);

        try {
            // Process the message
            $result = $chatbotService->processMessage(
                $branch,
                $this->phoneNumber,
                $this->message,
                $this->channel
            );

            // Send response via SMS
            if ($this->channel === ChatbotChannel::Sms) {
                $smsService->sendSms(
                    $this->phoneNumber,
                    $result['response']
                );
            }

            Log::info('ProcessChatbotMessageJob: Response sent', [
                'branch_id' => $this->branchId,
                'phone' => $this->phoneNumber,
                'intent' => $result['intent']->value,
                'confidence' => $result['confidence'],
                'conversation_id' => $result['conversation_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessChatbotMessageJob: Failed to process message', [
                'branch_id' => $this->branchId,
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessChatbotMessageJob failed', [
            'branch_id' => $this->branchId,
            'phone' => $this->phoneNumber,
            'exception' => $exception->getMessage(),
        ]);
    }
}
