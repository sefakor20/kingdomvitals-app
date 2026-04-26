<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Branch;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextTangoService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $senderId;

    public function __construct(?string $apiKey = null, ?string $senderId = null)
    {
        $this->baseUrl = config('services.texttango.base_url');
        $this->apiKey = $apiKey ?? config('services.texttango.api_key') ?? '';
        $this->senderId = $senderId ?? config('services.texttango.sender_id') ?? '';
    }

    /**
     * Create a TextTangoService instance for a specific branch.
     * Only uses branch-configured SMS credentials. Does NOT fall back to system config.
     */
    public static function forBranch(Branch $branch): self
    {
        $settings = $branch->settings ?? [];

        $encryptedApiKey = $settings['sms_api_key'] ?? null;
        $senderId = $settings['sms_sender_id'] ?? null;

        if (! empty($encryptedApiKey) && ! empty($senderId)) {
            try {
                $apiKey = Crypt::decryptString($encryptedApiKey);
            } catch (\Throwable) {
                // Legacy: key may be stored unencrypted from earlier versions.
                $apiKey = $encryptedApiKey;
            }

            return new self($apiKey, $senderId);
        }

        return new self('', '');
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Send bulk SMS to multiple recipients via the v2 campaigns endpoint.
     *
     * @param  array<string>  $phoneNumbers  Array of phone numbers in E.164 format
     * @return array{success: bool, tracking_id?: string|null, message?: string, data?: array, error?: string}
     */
    public function sendBulkSms(
        array $phoneNumbers,
        string $message,
        ?string $senderId = null,
        bool $isScheduled = false,
        ?string $scheduledAt = null,
        ?string $campaignName = null,
    ): array {
        try {
            $payload = [
                'from' => $senderId ?? $this->senderId,
                'body' => $message,
                'to' => $phoneNumbers,
                'flash' => false,
                'is_scheduled' => $isScheduled,
            ];

            if ($isScheduled && $scheduledAt) {
                $payload['scheduled_at'] = $scheduledAt;
            }

            if ($campaignName !== null && $campaignName !== '') {
                $payload['campaign_name'] = $campaignName;
            }

            $response = $this->client()->post('/campaigns', $payload);

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                return [
                    'success' => true,
                    'tracking_id' => $data['id'] ?? null,
                    'message' => $response->json('meta.message') ?? 'SMS sent successfully',
                    'data' => $data,
                ];
            }

            Log::error('TextTango SMS send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $this->extractError($response),
            ];
        } catch (\Exception $e) {
            Log::error('TextTango SMS exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track the status of a campaign. Returns campaign attributes plus the
     * v2 analytics summary (total_sent, delivered, failed, pending).
     *
     * @return array{success: bool, data?: array, summary?: array, error?: string}
     */
    public function trackCampaign(string $trackingId): array
    {
        try {
            $response = $this->client()->get("/campaigns/{$trackingId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data.attributes') ?? [],
                    'summary' => $response->json('data.analytics.summary') ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $this->extractError($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track a single message inside a campaign. v2 requires both ids.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function trackSingleMessage(string $campaignId, string $messageId): array
    {
        try {
            $response = $this->client()->get("/campaigns/{$campaignId}/messages/{$messageId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data.attributes') ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $this->extractError($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List messages within a campaign (paginated). Useful for backfilling
     * per-recipient ids after dispatch.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function listCampaignMessages(string $campaignId, ?string $status = null, int $perPage = 100): array
    {
        try {
            $query = ['per_page' => $perPage];
            if ($status !== null) {
                $query['status'] = $status;
            }

            $response = $this->client()->get("/campaigns/{$campaignId}/messages", $query);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $this->extractError($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the wallet balance from v2.
     *
     * @return array{success: bool, main_balance?: float, bonus_balance?: float, total_balance?: float, currency?: string, error?: string}
     */
    public function getBalance(): array
    {
        try {
            $response = $this->client()->get('/wallet');

            if ($response->successful()) {
                $attributes = $response->json('data.attributes') ?? [];

                $main = (float) ($attributes['main_balance'] ?? 0);
                $bonus = (float) ($attributes['bonus_balance'] ?? 0);

                return [
                    'success' => true,
                    'main_balance' => $main,
                    'bonus_balance' => $bonus,
                    'total_balance' => isset($attributes['total_balance'])
                        ? (float) $attributes['total_balance']
                        : $main + $bonus,
                    'currency' => $attributes['currency'] ?? 'GHS',
                ];
            }

            return [
                'success' => false,
                'error' => $this->extractError($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiKey !== '0' && ($this->senderId !== '' && $this->senderId !== '0');
    }

    public function getDefaultSenderId(): string
    {
        return $this->senderId;
    }

    /**
     * Extract a human-readable error from a v2 JSON:API error envelope, with
     * fallbacks for non-conforming responses.
     */
    protected function extractError(Response $response): string
    {
        $detail = $response->json('errors.0.detail');
        if (is_string($detail) && $detail !== '') {
            return $detail;
        }

        $title = $response->json('errors.0.title');
        if (is_string($title) && $title !== '') {
            return $title;
        }

        $message = $response->json('message');
        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'TextTango request failed (HTTP '.$response->status().')';
    }
}
