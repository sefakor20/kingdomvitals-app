<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TextTangoWebhookRequest;
use App\Models\Tenant;
use App\Models\Tenant\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TextTangoWebhookController extends Controller
{
    /**
     * Handle TextTango delivery status webhook.
     */
    public function handleDelivery(TextTangoWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // Extract data from payload
        $trackingId = $payload['tracking_id'] ?? null;
        $messageId = $payload['message_id'] ?? null;
        $status = $payload['status'] ?? null;
        $phoneNumber = $payload['phone_number'] ?? null;

        if (! $trackingId && ! $messageId) {
            Log::warning('TextTango webhook: Missing tracking_id and message_id');

            return response()->json(['status' => 'ignored', 'reason' => 'missing_identifiers']);
        }

        // Find the SmsLog across all tenants
        $result = $this->findSmsLogAcrossTenants($trackingId, $messageId, $phoneNumber);

        if (! $result) {
            Log::info('TextTango webhook: SmsLog not found', [
                'tracking_id' => $trackingId,
                'message_id' => $messageId,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'not_found']);
        }

        [$tenant, $smsLogId] = $result;

        // Initialize tenant context and update the record
        tenancy()->initialize($tenant);

        try {
            $this->updateSmsLogStatus($smsLogId, $status, $payload);

            Log::info('TextTango webhook: Delivery status updated', [
                'sms_log_id' => $smsLogId,
                'status' => $status,
                'tenant_id' => $tenant->id,
            ]);

            return response()->json(['status' => 'success']);
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Find SmsLog across all tenants by provider_message_id and/or phone_number.
     *
     * @return array{0: Tenant, 1: string}|null
     */
    protected function findSmsLogAcrossTenants(
        ?string $trackingId,
        ?string $messageId,
        ?string $phoneNumber
    ): ?array {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $query = SmsLog::query();

                // Build query based on available identifiers
                if ($trackingId) {
                    $query->where('provider_message_id', $trackingId);
                } elseif ($messageId) {
                    $query->where('provider_message_id', $messageId);
                }

                // If we have phone number, use it to narrow down for campaigns
                if ($phoneNumber && ($trackingId || $messageId)) {
                    $query->where('phone_number', $phoneNumber);
                }

                $smsLog = $query->first();

                if ($smsLog) {
                    $smsLogId = $smsLog->id;
                    tenancy()->end();

                    return [$tenant, $smsLogId];
                }
            } finally {
                tenancy()->end();
            }
        }

        return null;
    }

    /**
     * Update the SmsLog status based on webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function updateSmsLogStatus(string $smsLogId, string $status, array $payload): void
    {
        $smsLog = SmsLog::find($smsLogId);

        if (! $smsLog) {
            return;
        }

        $mappedStatus = $this->mapTextTangoStatus($status);

        $updateData = ['status' => $mappedStatus];

        if ($mappedStatus === SmsStatus::Delivered) {
            $updateData['delivered_at'] = now();
        }

        if ($mappedStatus === SmsStatus::Failed) {
            $updateData['error_message'] = $payload['error_message'] ?? $payload['reason'] ?? 'Delivery failed';
        }

        $smsLog->update($updateData);
    }

    /**
     * Map TextTango status strings to SmsStatus enum.
     */
    protected function mapTextTangoStatus(string $status): SmsStatus
    {
        return match (strtolower($status)) {
            'delivered', 'success' => SmsStatus::Delivered,
            'failed', 'rejected', 'expired', 'undeliverable' => SmsStatus::Failed,
            'sent', 'submitted', 'accepted' => SmsStatus::Sent,
            default => SmsStatus::Pending,
        };
    }
}
