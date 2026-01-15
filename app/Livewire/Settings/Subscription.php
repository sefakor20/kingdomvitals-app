<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\SubscriptionPlan;
use App\Services\PlanAccessService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Subscription extends Component
{
    /**
     * Get the current subscription plan.
     */
    #[Computed]
    public function plan(): ?SubscriptionPlan
    {
        return app(PlanAccessService::class)->getPlan();
    }

    /**
     * Get member quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function memberQuota(): array
    {
        return app(PlanAccessService::class)->getMemberQuota();
    }

    /**
     * Get branch quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function branchQuota(): array
    {
        return app(PlanAccessService::class)->getBranchQuota();
    }

    /**
     * Get SMS quota information.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function smsQuota(): array
    {
        return app(PlanAccessService::class)->getSmsQuota();
    }

    /**
     * Get storage quota information.
     *
     * @return array{used: float, max: int|null, unlimited: bool, remaining: float|null, percent: float}
     */
    #[Computed]
    public function storageQuota(): array
    {
        return app(PlanAccessService::class)->getStorageQuota();
    }

    /**
     * Get household quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function householdQuota(): array
    {
        return app(PlanAccessService::class)->getHouseholdQuota();
    }

    /**
     * Get cluster quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function clusterQuota(): array
    {
        return app(PlanAccessService::class)->getClusterQuota();
    }

    /**
     * Get visitor quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function visitorQuota(): array
    {
        return app(PlanAccessService::class)->getVisitorQuota();
    }

    /**
     * Get equipment quota information.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function equipmentQuota(): array
    {
        return app(PlanAccessService::class)->getEquipmentQuota();
    }

    /**
     * Get the list of enabled modules.
     *
     * @return array<string>
     */
    #[Computed]
    public function enabledModules(): array
    {
        return $this->plan?->enabled_modules ?? [];
    }

    /**
     * Get the list of enabled features.
     *
     * @return array<string>
     */
    #[Computed]
    public function features(): array
    {
        return $this->plan?->features ?? [];
    }

    /**
     * Get the support level.
     */
    #[Computed]
    public function supportLevel(): ?string
    {
        return $this->plan?->support_level?->value;
    }

    /**
     * Check if any quotas have limits (not all unlimited).
     */
    #[Computed]
    public function hasAnyQuotaLimits(): bool
    {
        return ! $this->memberQuota['unlimited']
            || ! $this->branchQuota['unlimited']
            || ! $this->smsQuota['unlimited']
            || ! $this->storageQuota['unlimited']
            || ! $this->householdQuota['unlimited']
            || ! $this->clusterQuota['unlimited']
            || ! $this->visitorQuota['unlimited']
            || ! $this->equipmentQuota['unlimited'];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.settings.subscription');
    }
}
