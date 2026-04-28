<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TextTangoWebhookRequest;
use App\Models\Tenant;
use App\Models\Tenant\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TextTangoWebhookController extends Controller
{
    /**
     * Handle TextTango delivery status webhook (v1 flat or v2 JSON:API payload).
     */
    public function handleDelivery(TextTangoWebhookRequest $request): JsonResponse
    {
        $payload = $request->all();
        $event = $this->normalize($payload);

        // Campaign-level events (e.g. campaign.status_updated) summarize a whole
        // campaign and don't carry per-recipient info. Updating SmsLogs from them
        // can regress per-recipient state set by message.* events.
        $eventName = \is_string($payload['event'] ?? null) ? $payload['event'] : null;
        if ($eventName !== null && str_starts_with($eventName, 'campaign.')) {
            return response()->json(['status' => 'ignored', 'reason' => 'campaign_event']);
        }

        if ($event['status'] === null) {
            Log::warning('TextTango webhook: Missing status', ['payload' => $payload]);

            return response()->json(['status' => 'ignored', 'reason' => 'missing_status']);
        }

        if ($event['campaign_id'] === null && $event['message_id'] === null && $event['recipient_id'] === null) {
            Log::warning('TextTango webhook: Missing identifiers');

            return response()->json(['status' => 'ignored', 'reason' => 'missing_identifiers']);
        }

        $result = $this->findSmsLogAcrossTenants($event);

        if (! $result) {
            Log::info('TextTango webhook: SmsLog not found', $event);

            return response()->json(['status' => 'ignored', 'reason' => 'not_found']);
        }

        [$tenant, $smsLogId] = $result;

        tenancy()->initialize($tenant);

        try {
            $this->updateSmsLogStatus($smsLogId, $event);

            Log::info('TextTango webhook: Delivery status updated', [
                'sms_log_id' => $smsLogId,
                'status' => $event['status'],
                'tenant_id' => $tenant->id,
            ]);

            return response()->json(['status' => 'success']);
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Normalize a webhook payload into a single internal shape, accepting
     * both v1 flat keys and v2 JSON:API envelopes.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     campaign_id: string|null,
     *     message_id: string|null,
     *     recipient_id: string|null,
     *     phone_number: string|null,
     *     status: string|null,
     *     delivered_at: string|null,
     *     error_message: string|null,
     * }
     */
    protected function normalize(array $payload): array
    {
        // v2 outbound webhook (flat, discriminated by `event`).
        // `message.status_updated` carries the per-recipient identifiers we need
        // to update an individual SmsLog. `campaign.*` events only provide the
        // campaign-level summary; we still surface the status so callers can
        // act on it, but with no `to` we cannot pin a specific recipient.
        if (isset($payload['event']) && \is_string($payload['event'])) {
            $isMessage = str_starts_with($payload['event'], 'message.');

            return [
                'campaign_id' => $this->stringOrNull($payload['campaign_id'] ?? null),
                'message_id' => $isMessage ? $this->stringOrNull($payload['message_id'] ?? null) : null,
                'recipient_id' => $isMessage ? $this->stringOrNull($payload['message_id'] ?? null) : null,
                'phone_number' => $this->stringOrNull($payload['to'] ?? null),
                'status' => $this->stringOrNull($payload['status'] ?? null),
                'delivered_at' => $this->stringOrNull(
                    $payload['delivered_at']
                    ?? $payload['failed_at']
                    ?? $payload['dispatched_at']
                    ?? $payload['timestamp']
                    ?? null,
                ),
                'error_message' => $this->stringOrNull(
                    $payload['error_message']
                    ?? $payload['failure_reason']
                    ?? null,
                ),
            ];
        }

        // v2 JSON:API payload (used by some endpoints / older webhooks)
        if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['attributes'])) {
            $data = $payload['data'];
            $attributes = $data['attributes'] ?? [];
            $type = $data['type'] ?? null;

            $campaignId = $data['relationships']['campaign']['data']['id'] ?? null;
            $messageId = $type === 'message' ? ($data['id'] ?? null) : null;
            if ($type === 'campaign') {
                $campaignId = $data['id'] ?? $campaignId;
            }

            return [
                'campaign_id' => $this->stringOrNull($campaignId),
                'message_id' => $this->stringOrNull($messageId),
                'recipient_id' => $this->stringOrNull($messageId),
                'phone_number' => $this->stringOrNull($attributes['to'] ?? null),
                'status' => $this->stringOrNull($attributes['status'] ?? null),
                'delivered_at' => $this->stringOrNull(
                    $attributes['delivered_at']
                    ?? $attributes['failed_at']
                    ?? $attributes['dispatched_at']
                    ?? null,
                ),
                'error_message' => $this->stringOrNull(
                    $attributes['delivery_details']['description']
                    ?? $attributes['delivery_details']['detailed_status']
                    ?? null,
                ),
            ];
        }

        // v1 flat payload
        return [
            'campaign_id' => $this->stringOrNull($payload['tracking_id'] ?? null),
            'message_id' => $this->stringOrNull($payload['message_id'] ?? null),
            'recipient_id' => null,
            'phone_number' => $this->stringOrNull($payload['phone_number'] ?? null),
            'status' => $this->stringOrNull($payload['status'] ?? null),
            'delivered_at' => $this->stringOrNull($payload['delivered_at'] ?? $payload['timestamp'] ?? null),
            'error_message' => $this->stringOrNull($payload['error_message'] ?? $payload['reason'] ?? null),
        ];
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = (string) $value;

        return $string === '' ? null : $string;
    }

    /**
     * @param  array{campaign_id: string|null, message_id: string|null, recipient_id: string|null, phone_number: string|null}  $event
     * @return array{0: Tenant, 1: string}|null
     */
    protected function findSmsLogAcrossTenants(array $event): ?array
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $smsLogId = $this->findSmsLogIdForEvent($event);

                if ($smsLogId !== null) {
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
     * Try a series of progressively-broader lookups for the given event, in
     * order of specificity. We don't store the per-recipient `message_id` until
     * the first webhook arrives — so for a freshly-sent SMS the row only has
     * `provider_message_id` set to the campaign id, and we need campaign+phone
     * to find it.
     *
     * @param  array{campaign_id: string|null, message_id: string|null, recipient_id: string|null, phone_number: string|null}  $event
     */
    protected function findSmsLogIdForEvent(array $event): ?string
    {
        // 1. Direct recipient match (later webhooks for an already-tracked row).
        if ($event['recipient_id']) {
            $smsLog = SmsLog::query()
                ->where(function ($q) use ($event) {
                    $q->where('provider_recipient_id', $event['recipient_id'])
                        ->orWhere('provider_message_id', $event['recipient_id']);
                })
                ->first();
            if ($smsLog) {
                return $smsLog->id;
            }
        }

        // 2. Direct message match.
        if ($event['message_id']) {
            $smsLog = SmsLog::query()->where('provider_message_id', $event['message_id'])->first();
            if ($smsLog) {
                return $smsLog->id;
            }
        }

        // 3. Campaign + phone (first delivery webhook for a brand-new recipient).
        if ($event['campaign_id'] && $event['phone_number']) {
            $smsLog = SmsLog::query()
                ->where('provider_message_id', $event['campaign_id'])
                ->where('phone_number', $event['phone_number'])
                ->first();
            if ($smsLog) {
                return $smsLog->id;
            }
        }

        // 4. Campaign only (fallback when phone is unknown).
        if ($event['campaign_id']) {
            $smsLog = SmsLog::query()->where('provider_message_id', $event['campaign_id'])->first();
            if ($smsLog) {
                return $smsLog->id;
            }
        }

        return null;
    }

    /**
     * @param  array{status: string|null, delivered_at: string|null, error_message: string|null, recipient_id: string|null}  $event
     */
    protected function updateSmsLogStatus(string $smsLogId, array $event): void
    {
        $smsLog = SmsLog::find($smsLogId);

        if (! $smsLog) {
            return;
        }

        $mappedStatus = $this->mapTextTangoStatus((string) $event['status']);

        $updateData = ['status' => $mappedStatus];

        if ($mappedStatus === SmsStatus::Delivered) {
            $updateData['delivered_at'] = $event['delivered_at']
                ? Carbon::parse($event['delivered_at'])
                : now();
        }

        if ($mappedStatus === SmsStatus::Failed && $event['error_message']) {
            $updateData['error_message'] = $event['error_message'];
        } elseif ($mappedStatus === SmsStatus::Failed) {
            $updateData['error_message'] = 'Delivery failed';
        }

        // Backfill the per-recipient id when the v2 webhook supplies it.
        if ($event['recipient_id'] && empty($smsLog->provider_recipient_id)) {
            $updateData['provider_recipient_id'] = $event['recipient_id'];
        }

        $smsLog->update($updateData);
    }

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
