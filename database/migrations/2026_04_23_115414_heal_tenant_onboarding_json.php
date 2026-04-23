<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Repair tenants whose `data.onboarding` value was double-encoded as a JSON
        // string (one level or many) instead of a nested JSON object. Caused by the
        // now-removed `'onboarding' => 'array'` cast on the Tenant model clashing with
        // stancl/tenancy's own serialization of virtual columns inside `data`.
        DB::table('tenants')->orderBy('id')->each(function (object $tenant): void {
            if (empty($tenant->data)) {
                return;
            }

            $data = json_decode((string) $tenant->data, true);
            if (! is_array($data) || ! array_key_exists('onboarding', $data)) {
                return;
            }

            $onboarding = $data['onboarding'];

            // Already healthy — an array/object. Nothing to do.
            if (is_array($onboarding)) {
                return;
            }

            // Repeatedly decode the stringified JSON until we reach an array (or give up).
            $decoded = $onboarding;
            $guard = 0;
            while (is_string($decoded) && $guard < 20) {
                $next = json_decode($decoded, true);
                if ($next === null && json_last_error() !== JSON_ERROR_NONE) {
                    // Can't parse any further; treat as lost and reset below.
                    $decoded = null;
                    break;
                }
                $decoded = $next;
                $guard++;
            }

            if (! is_array($decoded)) {
                // Fall back to a fresh initial-state blob so the tenant can redo onboarding.
                $decoded = [
                    'completed' => false,
                    'completed_at' => null,
                    'current_step' => 1,
                    'steps' => [
                        'organization' => ['completed' => false, 'skipped' => false],
                        'team' => ['completed' => false, 'skipped' => false],
                        'integrations' => ['completed' => false, 'skipped' => false],
                        'services' => ['completed' => false, 'skipped' => false],
                    ],
                    'branch_id' => null,
                ];
            }

            $data['onboarding'] = $decoded;

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['data' => json_encode($data)]);
        });
    }

    public function down(): void
    {
        // Healing the JSON cannot be sensibly reversed.
    }
};
