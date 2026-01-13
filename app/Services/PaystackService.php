<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Branch;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackService
{
    protected string $baseUrl = 'https://api.paystack.co';

    protected string $secretKey;

    protected string $publicKey;

    protected bool $testMode;

    /**
     * Create a new PaystackService instance.
     */
    public function __construct(?string $secretKey = null, ?string $publicKey = null, bool $testMode = false)
    {
        $this->secretKey = $secretKey ?? '';
        $this->publicKey = $publicKey ?? '';
        $this->testMode = $testMode;
    }

    /**
     * Create a PaystackService instance for a specific branch.
     */
    public static function forBranch(Branch $branch): self
    {
        $settings = $branch->settings ?? [];

        $encryptedSecretKey = $settings['paystack_secret_key'] ?? null;
        $encryptedPublicKey = $settings['paystack_public_key'] ?? null;
        $testMode = $settings['paystack_test_mode'] ?? true;

        $secretKey = '';
        $publicKey = '';

        if (! empty($encryptedSecretKey)) {
            try {
                $secretKey = Crypt::decryptString($encryptedSecretKey);
            } catch (\Exception $e) {
                $secretKey = $encryptedSecretKey;
            }
        }

        if (! empty($encryptedPublicKey)) {
            try {
                $publicKey = Crypt::decryptString($encryptedPublicKey);
            } catch (\Exception $e) {
                $publicKey = $encryptedPublicKey;
            }
        }

        return new self($secretKey, $publicKey, (bool) $testMode);
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson();
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

            // Amount should be in kobo (1 GHS = 100 pesewas)
            $response = $this->client()->post('/transaction/initialize', $data);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'reference' => $data['reference'],
                ];
            }

            Log::error('Paystack initialize failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to initialize transaction',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialize exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a transaction.
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
            Log::error('Paystack verify exception', [
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
     * Create or get a customer.
     *
     * @param  array{email: string, first_name?: string, last_name?: string, phone?: string}  $data
     * @return array{success: bool, data?: array, error?: string}
     */
    public function createCustomer(array $data): array
    {
        try {
            $response = $this->client()->post('/customer', $data);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to create customer',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a subscription plan.
     *
     * @param  array{name: string, amount: int, interval: string}  $data
     * @return array{success: bool, data?: array, error?: string}
     */
    public function createPlan(array $data): array
    {
        try {
            $response = $this->client()->post('/plan', $data);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to create plan',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a subscription.
     *
     * @param  array{customer: string, plan: string, authorization?: string}  $data
     * @return array{success: bool, data?: array, error?: string}
     */
    public function createSubscription(array $data): array
    {
        try {
            $response = $this->client()->post('/subscription', $data);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to create subscription',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disable (cancel) a subscription.
     *
     * @return array{success: bool, error?: string}
     */
    public function cancelSubscription(string $code, string $emailToken): array
    {
        try {
            $response = $this->client()->post('/subscription/disable', [
                'code' => $code,
                'token' => $emailToken,
            ]);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Failed to cancel subscription',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the public key for Paystack Inline JS.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return $this->secretKey !== '' && $this->secretKey !== '0' && ($this->publicKey !== '' && $this->publicKey !== '0');
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
        return 'KV-'.strtoupper(Str::random(16));
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
