<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\PlanModule;
use App\Services\PlanAccessService;

trait ChecksPlanAccess
{
    /**
     * Check if a module is enabled for the current tenant's plan.
     */
    protected function moduleEnabled(PlanModule $module): bool
    {
        return app(PlanAccessService::class)->hasModule($module);
    }

    /**
     * Check if a feature is enabled for the current tenant's plan.
     */
    protected function featureEnabled(string $feature): bool
    {
        return app(PlanAccessService::class)->hasFeature($feature);
    }

    /**
     * Check if more members can be created within the quota.
     */
    protected function canCreateMoreMembers(): bool
    {
        return app(PlanAccessService::class)->canCreateMember();
    }

    /**
     * Check if more branches can be created within the quota.
     */
    protected function canCreateMoreBranches(): bool
    {
        return app(PlanAccessService::class)->canCreateBranch();
    }

    /**
     * Check if SMS can be sent (has remaining credits).
     */
    protected function canSendMoreSms(int $count = 1): bool
    {
        return app(PlanAccessService::class)->canSendSms($count);
    }
}
