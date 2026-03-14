<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\ChatbotChannel;
use App\Http\Controllers\Controller;
use App\Jobs\AI\ProcessChatbotMessageJob;
use App\Models\Tenant\Branch;
use App\Services\TextTangoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsInboundController extends Controller
{
    public function __construct(
        protected TextTangoService $smsService
    ) {}

    /**
     * Handle inbound SMS webhook from TextTango.
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('SmsInboundController: Received inbound SMS', [
            'payload' => $request->all(),
        ]);

        // Validate required fields
        $phoneNumber = $request->input('from') ?? $request->input('sender');
        $message = $request->input('message') ?? $request->input('text') ?? $request->input('body');
        $toNumber = $request->input('to') ?? $request->input('recipient');

        if (! $phoneNumber || ! $message) {
            Log::warning('SmsInboundController: Missing required fields', [
                'has_phone' => (bool) $phoneNumber,
                'has_message' => (bool) $message,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        // Find the branch associated with this number
        $branch = $this->findBranchByNumber($toNumber);

        if (! $branch) {
            Log::warning('SmsInboundController: No branch found for number', [
                'to_number' => $toNumber,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Unknown recipient'], 404);
        }

        // Dispatch job for async processing
        ProcessChatbotMessageJob::dispatch(
            $branch->id,
            $phoneNumber,
            $message,
            ChatbotChannel::Sms
        );

        return response()->json(['status' => 'received']);
    }

    /**
     * Find branch by SMS number.
     */
    protected function findBranchByNumber(?string $number): ?Branch
    {
        if (! $number) {
            // Return first branch as default (for single-tenant setups)
            return Branch::first();
        }

        // Try to find branch with matching SMS number
        return Branch::where('sms_number', $number)
            ->orWhere('phone', $number)
            ->first() ?? Branch::first();
    }
}
