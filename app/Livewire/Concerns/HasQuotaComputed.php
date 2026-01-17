<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\QuotaType;
use App\Services\PlanAccessService;
use Livewire\Attributes\Computed;

trait HasQuotaComputed
{
    #[Computed]
    public function memberQuota(): array
    {
        return app(PlanAccessService::class)->getMemberQuota();
    }

    #[Computed]
    public function branchQuota(): array
    {
        return app(PlanAccessService::class)->getBranchQuota();
    }

    #[Computed]
    public function householdQuota(): array
    {
        return app(PlanAccessService::class)->getHouseholdQuota();
    }

    #[Computed]
    public function clusterQuota(): array
    {
        return app(PlanAccessService::class)->getClusterQuota();
    }

    #[Computed]
    public function visitorQuota(): array
    {
        return app(PlanAccessService::class)->getVisitorQuota();
    }

    #[Computed]
    public function equipmentQuota(): array
    {
        return app(PlanAccessService::class)->getEquipmentQuota();
    }

    #[Computed]
    public function smsQuota(): array
    {
        return app(PlanAccessService::class)->getSmsQuota();
    }

    #[Computed]
    public function storageQuota(): array
    {
        return app(PlanAccessService::class)->getStorageQuota();
    }

    public function showQuotaWarningFor(QuotaType $type, int $threshold = 80): bool
    {
        return app(PlanAccessService::class)->isQuotaWarning($type->value, $threshold);
    }

    public function canCreateWithinQuotaFor(QuotaType $type): bool
    {
        return app(PlanAccessService::class)->canCreate($type);
    }
}
