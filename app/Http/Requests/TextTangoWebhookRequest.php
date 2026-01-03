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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tracking_id' => ['nullable', 'string', 'max:255'],
            'message_id' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', 'max:50'],
            'error_message' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
            'delivered_at' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The delivery status is required.',
        ];
    }
}
