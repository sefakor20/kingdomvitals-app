<?php

declare(strict_types=1);

namespace App\Livewire\Upgrade;

use App\Models\SubscriptionPlan;
use App\Services\PlanAccessService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PlansIndex extends Component
{
    public string $billingCycle = 'monthly';

    /**
     * Get all active subscription plans.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('price_monthly')
            ->get();
    }

    /**
     * Get the tenant's current plan.
     */
    #[Computed]
    public function currentPlan(): ?SubscriptionPlan
    {
        return app(PlanAccessService::class)->getPlan();
    }

    /**
     * Get the tenant's current plan ID.
     */
    #[Computed]
    public function currentPlanId(): ?string
    {
        return tenant()?->subscription_id;
    }

    /**
     * Check if a plan is the tenant's current plan.
     */
    public function isCurrentPlan(string $planId): bool
    {
        return $this->currentPlanId === $planId;
    }

    /**
     * Navigate to checkout for the selected plan.
     */
    public function selectPlan(string $planId): void
    {
        if ($this->isCurrentPlan($planId)) {
            return;
        }

        $this->redirectRoute('plans.checkout', [
            'plan' => $planId,
            'cycle' => $this->billingCycle,
        ], navigate: true);
    }

    /**
     * Toggle the billing cycle between monthly and annual.
     */
    public function toggleBillingCycle(): void
    {
        $this->billingCycle = $this->billingCycle === 'monthly' ? 'annual' : 'monthly';
    }

    /**
     * Set the billing cycle.
     */
    public function setBillingCycle(string $cycle): void
    {
        if (in_array($cycle, ['monthly', 'annual'])) {
            $this->billingCycle = $cycle;
        }
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.upgrade.plans-index');
    }
}
