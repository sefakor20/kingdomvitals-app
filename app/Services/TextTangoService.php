<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Branch;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextTangoService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $senderId;

    /**
     * Create a new TextTangoService instance.
     *
     * If credentials are not provided, uses system config values.
     * For tenant-specific messaging, use forBranch() instead.
     */
    public function __construct(?string $apiKey = null, ?string $senderId = null)
    {
        $this->baseUrl = config('services.texttango.base_url');
        $this->apiKey = $apiKey ?? config('services.texttango.api_key') ?? '';
        $this->senderId = $senderId ?? config('services.texttango.sender_id') ?? '';
    }

    /**
     * Create a TextTangoService instance for a specific branch.
     * Uses branch SMS settings if configured, falls back to system config.
     */
    public static function forBranch(Branch $branch): self
    {
        $settings = $branch->settings ?? [];

        $apiKey = $settings['sms_api_key'] ?? null;
        $senderId = $settings['sms_sender_id'] ?? null;

        // If branch has SMS settings configured, use them
        if (! empty($apiKey) && ! empty($senderId)) {
            return new self($apiKey, $senderId);
        }

        // Otherwise fall back to system config
        return new self;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Send bulk SMS to multiple recipients.
     *
     * @param  array<string>  $phoneNumbers  Array of phone numbers in E.164 format
     * @param  string  $message  The SMS message content
     * @param  string|null  $senderId  Optional sender ID override
     * @param  bool  $isScheduled  Whether to schedule the SMS
     * @param  string|null  $scheduledAt  Datetime for scheduled SMS
     * @return array{success: bool, tracking_id?: string, message?: string, error?: string}
     */
    public function sendBulkSms(
        array $phoneNumbers,
        string $message,
        ?string $senderId = null,
        bool $isScheduled = false,
        ?string $scheduledAt = null
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
                $payload['is_scheduled_datetime'] = $scheduledAt;
            }

            $response = $this->client()->post('/sms/campaign/send', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'tracking_id' => $data['data']['tracking_id'] ?? null,
                    'message' => $data['message'] ?? 'SMS sent successfully',
                    'data' => $data['data'] ?? [],
                ];
            }

            Log::error('TextTango SMS send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to send SMS',
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
     * Track the status of a bulk SMS campaign.
     *
     * @param  string  $trackingId  The campaign tracking ID
     * @return array{success: bool, data?: array, error?: string}
     */
    public function trackCampaign(string $trackingId): array
    {
        try {
            $response = $this->client()->get("/sms/campaign/track/{$trackingId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to track campaign',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track the status of a single SMS message.
     *
     * @param  string  $messageId  The message ID from the provider
     * @return array{success: bool, data?: array, error?: string}
     */
    public function trackSingleMessage(string $messageId): array
    {
        try {
            $response = $this->client()->get("/sms/campaign/track/single/{$messageId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to track message',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the account balance.
     *
     * @return array{success: bool, balance?: float, currency?: string, error?: string}
     */
    public function getBalance(): array
    {
        try {
            $response = $this->client()->get('/account/me/balance');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                return [
                    'success' => true,
                    'balance' => $data['main_account_balance'] ?? 0,
                    'currency' => $data['currency'] ?? 'GHS',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to get balance',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->senderId);
    }

    /**
     * Get the default sender ID.
     */
    public function getDefaultSenderId(): string
    {
        return $this->senderId;
    }
}
