<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Revenue;

use App\Enums\TenantStatus;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Component;

class RevenueDashboard extends Component
{
    public function render(): View
    {
        return view('livewire.super-admin.revenue.revenue-dashboard', [
            'metrics' => $this->getRevenueMetrics(),
            'planDistribution' => $this->getPlanDistribution(),
            'trends' => $this->getTrends(),
        ])->layout('components.layouts.superadmin.app');
    }

    /**
     * Calculate key revenue metrics.
     *
     * @return array{mrr: string, mrrRaw: float, arr: string, arrRaw: float, activeCount: int, trialCount: int, conversionRate: float, churnCount: int}
     */
    private function getRevenueMetrics(): array
    {
        $activeCount = Tenant::where('status', TenantStatus::Active)->count();
        $trialCount = Tenant::where('status', TenantStatus::Trial)->count();
        $suspendedCount = Tenant::where('status', TenantStatus::Suspended)->count();
        $inactiveCount = Tenant::where('status', TenantStatus::Inactive)->count();

        // Calculate MRR: Sum of (active tenants × their plan's monthly price)
        $mrr = Tenant::where('status', TenantStatus::Active)
            ->whereNotNull('subscription_id')
            ->with('subscriptionPlan')
            ->get()
            ->sum(fn (Tenant $tenant) => (float) ($tenant->subscriptionPlan?->price_monthly ?? 0));

        $arr = $mrr * 12;

        $churnCount = $suspendedCount + $inactiveCount;

        // Conversion rate = active / (active + trial + churned) × 100
        $totalRelevant = $activeCount + $trialCount + $churnCount;
        $conversionRate = $totalRelevant > 0
            ? round(($activeCount / $totalRelevant) * 100, 1)
            : 0;

        return [
            'mrr' => Number::currency($mrr, in: 'GHS'),
            'mrrRaw' => $mrr,
            'arr' => Number::currency($arr, in: 'GHS'),
            'arrRaw' => $arr,
            'activeCount' => $activeCount,
            'trialCount' => $trialCount,
            'conversionRate' => $conversionRate,
            'churnCount' => $churnCount,
        ];
    }

    /**
     * Get plan distribution data.
     *
     * @return Collection<int, array{plan: SubscriptionPlan, tenantCount: int, revenue: float, revenueFormatted: string, percentage: float}>
     */
    private function getPlanDistribution(): Collection
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $totalActiveTenants = Tenant::where('status', TenantStatus::Active)->count();

        return $plans->map(function (SubscriptionPlan $plan) use ($totalActiveTenants) {
            $tenantCount = Tenant::where('subscription_id', $plan->id)
                ->where('status', TenantStatus::Active)
                ->count();

            $revenue = $tenantCount * (float) $plan->price_monthly;
            $percentage = $totalActiveTenants > 0
                ? round(($tenantCount / $totalActiveTenants) * 100, 1)
                : 0;

            return [
                'plan' => $plan,
                'tenantCount' => $tenantCount,
                'revenue' => $revenue,
                'revenueFormatted' => Number::currency($revenue, in: 'GHS'),
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Get monthly trends (new subscribers, churned, net growth).
     *
     * @return array{newThisMonth: int, churnedThisMonth: int, netGrowth: int}
     */
    private function getTrends(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // New active subscribers this month
        $newThisMonth = Tenant::where('status', TenantStatus::Active)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Churned this month (suspended or set inactive this month)
        $churnedThisMonth = Tenant::whereIn('status', [TenantStatus::Suspended, TenantStatus::Inactive])
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('suspended_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('updated_at', [$startOfMonth, $endOfMonth]);
            })
            ->count();

        return [
            'newThisMonth' => $newThisMonth,
            'churnedThisMonth' => $churnedThisMonth,
            'netGrowth' => $newThisMonth - $churnedThisMonth,
        ];
    }
}
