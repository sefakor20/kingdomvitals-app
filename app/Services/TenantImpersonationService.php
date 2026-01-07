<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Models\TenantImpersonationLog;

class TenantImpersonationService
{
    public const SESSION_KEY = 'super_admin_impersonation';

    public const MAX_DURATION_MINUTES = 60;

    public function startImpersonation(
        SuperAdmin $superAdmin,
        Tenant $tenant,
        string $reason
    ): TenantImpersonationLog {
        $log = TenantImpersonationLog::startSession($superAdmin, $tenant, $reason);

        SuperAdminActivityLog::log(
            superAdmin: $superAdmin,
            action: 'tenant_impersonation_started',
            description: "Started impersonating tenant: {$tenant->name}",
            tenant: $tenant,
            metadata: [
                'reason' => $reason,
                'impersonation_log_id' => $log->id,
            ],
        );

        return $log;
    }

    public function endImpersonation(string $token): bool
    {
        $log = TenantImpersonationLog::where('token', $token)
            ->whereNull('ended_at')
            ->first();

        if (! $log) {
            return false;
        }

        $log->endSession();

        SuperAdminActivityLog::log(
            superAdmin: $log->superAdmin,
            action: 'tenant_impersonation_ended',
            description: "Ended impersonation of tenant: {$log->tenant->name}",
            tenant: $log->tenant,
            metadata: [
                'duration_minutes' => $log->duration_minutes,
                'impersonation_log_id' => $log->id,
            ],
        );

        return true;
    }

    public function getActiveSession(string $token): ?TenantImpersonationLog
    {
        $log = TenantImpersonationLog::where('token', $token)
            ->whereNull('ended_at')
            ->first();

        if (! $log || $log->isExpired(self::MAX_DURATION_MINUTES)) {
            return null;
        }

        return $log;
    }

    public function buildImpersonationUrl(Tenant $tenant, TenantImpersonationLog $log): string
    {
        $domain = $tenant->domains()->first()?->domain;

        if (! $domain) {
            throw new \RuntimeException('Tenant has no configured domain');
        }

        // Use HTTPS when running locally with Herd (secured sites) or in production
        $scheme = request()->isSecure() ? 'https' : 'http';

        return "{$scheme}://{$domain}/impersonate/enter?token={$log->token}";
    }
}
