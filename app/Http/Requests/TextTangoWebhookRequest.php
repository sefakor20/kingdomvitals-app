<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
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
     *
     * Resolution order:
     *   1. If a {branchId} segment is in the URL, look up that branch's stored
     *      webhook secret and verify against it.
     *   2. If the branch secret doesn't match (or isn't stored), fall back to
     *      the global TEXTTANGO_WEBHOOK_SECRET env var. This covers the
     *      central-domain TextTango account and not-yet-migrated branches.
     */
    protected function validateWebhookSignature(): bool
    {
        $branchId = $this->route('branchId');
        $branchSecret = \is_string($branchId) ? $this->resolveBranchWebhookSecret($branchId) : null;
        $globalSecret = config('services.texttango.webhook_secret');

        // Local/testing escape hatch: if no secret is configured anywhere, let
        // the request through so developers can prod the webhook without setup.
        if ($branchSecret === null && empty($globalSecret)) {
            Log::warning('TextTango webhook: No webhook secret configured');

            return app()->environment('local', 'testing');
        }

        $signature = $this->header('X-TextTango-Signature')
            ?? $this->header('X-Webhook-Signature');

        if (! \is_string($signature) || $signature === '') {
            Log::warning('TextTango webhook: Missing signature header');

            return false;
        }

        $payload = $this->getContent();
        if (! \is_string($payload)) {
            return false;
        }

        if ($branchSecret !== null && hash_equals(hash_hmac('sha256', $payload, $branchSecret), $signature)) {
            return true;
        }

        if (! empty($globalSecret) && hash_equals(hash_hmac('sha256', $payload, (string) $globalSecret), $signature)) {
            return true;
        }

        Log::warning('TextTango webhook: Invalid signature', [
            'branch_id' => $branchId,
            'tried_branch_secret' => $branchSecret !== null,
        ]);

        return false;
    }

    /**
     * Walk every tenant looking for the matching branch and return its
     * decrypted webhook secret, or null if not found / not configured.
     */
    protected function resolveBranchWebhookSecret(string $branchId): ?string
    {
        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $branch = Branch::find($branchId);

                if ($branch !== null) {
                    return $branch->getSmsWebhookSecret();
                }
            } finally {
                tenancy()->end();
            }
        }

        return null;
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
