<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlatformPaystackService
{
    protected string $baseUrl = 'https://api.paystack.co';

    protected string $secretKey;

    protected string $publicKey;

    protected bool $testMode;

    public function __construct(SystemSettingService $settingsService)
    {
        $credentials = $settingsService->getDefaultPaystackCredentials();

        $this->secretKey = $credentials['secret_key'] ?? '';
        $this->publicKey = $credentials['public_key'] ?? '';
        $this->testMode = $credentials['test_mode'] ?? true;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Verify a transaction by reference.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = $this->client()->get("/transaction/verify/{$reference}");

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');

                return [
                    'success' => $data['status'] === 'success',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to verify transaction',
            ];
        } catch (\Exception $e) {
            Log::error('Platform Paystack verify exception', [
                'message' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize a transaction.
     *
     * @param  array{email: string, amount: int, reference?: string, callback_url?: string, metadata?: array}  $data
     * @return array{success: bool, data?: array, error?: string}
     */
    public function initializeTransaction(array $data): array
    {
        try {
            if (empty($data['reference'])) {
                $data['reference'] = $this->generateReference();
            }

            $response = $this->client()->post('/transaction/initialize', $data);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'reference' => $data['reference'],
                ];
            }

            Log::error('Platform Paystack initialize failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to initialize transaction',
            ];
        } catch (\Exception $e) {
            Log::error('Platform Paystack initialize exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the service is configured with valid credentials.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }

    /**
     * Get the public key for Paystack Inline JS.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Check if running in test mode.
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Generate a unique reference for a transaction.
     */
    public function generateReference(): string
    {
        return 'PLATFORM-'.strtoupper(Str::random(16));
    }

    /**
     * Validate a webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Convert amount to kobo/pesewas (smallest currency unit).
     */
    public static function toKobo(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert kobo/pesewas back to main currency unit.
     */
    public static function fromKobo(int $kobo): float
    {
        return $kobo / 100;
    }
}
