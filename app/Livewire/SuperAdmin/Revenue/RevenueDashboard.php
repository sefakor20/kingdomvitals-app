<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Revenue;

use App\Enums\TenantStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueDashboard extends Component
{
    use HasReportExport;

    public function exportCsv(): StreamedResponse
    {
        $planDistribution = $this->getPlanDistribution();
        $metrics = $this->getRevenueMetrics();

        $data = $planDistribution->map(fn (array $item): array => [
            'plan_name' => $item['plan']->name,
            'monthly_price' => Number::currency((float) $item['plan']->price_monthly, in: 'GHS'),
            'active_subscribers' => $item['tenantCount'],
            'monthly_revenue' => $item['revenueFormatted'],
            'percentage_of_total' => $item['percentage'].'%',
        ]);

        $headers = [
            'Plan Name',
            'Monthly Price (GHS)',
            'Active Subscribers',
            'Monthly Revenue (GHS)',
            '% of Total',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_revenue',
            description: 'Exported revenue report to CSV',
            metadata: [
                'mrr' => $metrics['mrrRaw'],
                'arr' => $metrics['arrRaw'],
                'plan_count' => $planDistribution->count(),
            ],
        );

        $filename = 'revenue-report-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

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
            ->sum(fn (Tenant $tenant): float => (float) ($tenant->subscriptionPlan?->price_monthly ?? 0));

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

        return $plans->map(function (SubscriptionPlan $plan) use ($totalActiveTenants): array {
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
            ->where(function ($query) use ($startOfMonth, $endOfMonth): void {
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
