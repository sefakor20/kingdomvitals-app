<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class TextTangoWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->validateWebhookSignature();
    }

    /**
     * Validate the webhook signature using HMAC.
     */
    protected function validateWebhookSignature(): bool
    {
        $webhookSecret = config('services.texttango.webhook_secret');

        // If no secret configured, log warning but allow in local/testing
        if (empty($webhookSecret)) {
            Log::warning('TextTango webhook: No webhook secret configured');

            return app()->environment('local', 'testing');
        }

        // Check for signature in header
        $signature = $this->header('X-TextTango-Signature')
            ?? $this->header('X-Webhook-Signature');

        if (! $signature) {
            Log::warning('TextTango webhook: Missing signature header');

            return false;
        }

        // Verify HMAC signature
        $payload = $this->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('TextTango webhook: Invalid signature');

            return false;
        }

        return true;
    }

    /**
     * No rules: payload shape differs between v1 (flat) and v2 (JSON:API),
     * and the HMAC check above already authenticates the request body.
     * Shape normalization and field validation happen in the controller.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
