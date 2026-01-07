<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Analytics;

use App\Enums\TenantStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Livewire\Concerns\HasReportFilters;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Models\TenantUsageSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsageAnalytics extends Component
{
    use HasReportExport;
    use HasReportFilters;

    private const CACHE_TTL = 300; // 5 minutes

    #[Url]
    public string $sortBy = 'active_members';

    #[Url]
    public string $sortDirection = 'desc';

    public int $topTenantsLimit = 10;

    protected function clearReportCaches(): void
    {
        Cache::forget('usage_analytics_overview');
        Cache::forget('usage_analytics_feature_adoption');
        unset($this->overviewStats, $this->featureAdoption, $this->tenantsApproachingLimits, $this->topTenants);
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }

        unset($this->topTenants);
    }

    #[Computed]
    public function overviewStats(): array
    {
        $today = now()->toDateString();
        $snapshots = TenantUsageSnapshot::forDate($today)->get();

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', TenantStatus::Active)->count();
        $trialTenants = Tenant::where('status', TenantStatus::Trial)->count();

        return [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'trialTenants' => $trialTenants,
            'totalMembers' => (int) $snapshots->sum('active_members'),
            'totalSmsSent' => (int) $snapshots->sum('sms_sent_this_month'),
            'totalDonations' => Number::currency((float) $snapshots->sum('donations_this_month'), in: 'GHS'),
            'totalDonationsRaw' => (float) $snapshots->sum('donations_this_month'),
            'avgMembersPerTenant' => $snapshots->count() > 0
                ? round($snapshots->avg('active_members'), 1)
                : 0,
        ];
    }

    #[Computed]
    public function tenantsApproachingLimits(): Collection
    {
        $today = now()->toDateString();
        $threshold = 80;

        return TenantUsageSnapshot::with('tenant.subscriptionPlan')
            ->forDate($today)
            ->approachingLimits($threshold)
            ->orderByDesc('member_quota_usage_percent')
            ->limit(10)
            ->get()
            ->map(function (TenantUsageSnapshot $snapshot) use ($threshold) {
                return [
                    'tenant' => $snapshot->tenant,
                    'alerts' => $snapshot->getQuotaAlerts($threshold),
                ];
            })
            ->filter(fn (array $item) => count($item['alerts']) > 0);
    }

    #[Computed]
    public function featureAdoption(): array
    {
        $today = now()->toDateString();
        $snapshots = TenantUsageSnapshot::forDate($today)->get();
        $totalTenants = $snapshots->count();

        if ($totalTenants === 0) {
            return [];
        }

        $modules = [
            'members' => 'Members',
            'donations' => 'Donations',
            'attendance' => 'Attendance',
            'sms' => 'SMS',
            'visitors' => 'Visitors',
            'expenses' => 'Expenses',
            'pledges' => 'Pledges',
            'clusters' => 'Clusters',
            'prayer' => 'Prayer Requests',
        ];

        $adoption = [];

        foreach ($modules as $key => $label) {
            $count = $snapshots->filter(function (TenantUsageSnapshot $snapshot) use ($key) {
                return in_array($key, $snapshot->active_modules ?? [], true);
            })->count();

            $adoption[$key] = [
                'label' => $label,
                'count' => $count,
                'percentage' => round(($count / $totalTenants) * 100, 1),
            ];
        }

        // Sort by percentage descending
        uasort($adoption, fn (array $a, array $b) => $b['percentage'] <=> $a['percentage']);

        return $adoption;
    }

    #[Computed]
    public function topTenants(): Collection
    {
        $today = now()->toDateString();

        $query = TenantUsageSnapshot::with('tenant.subscriptionPlan')
            ->forDate($today)
            ->whereHas('tenant', function ($q) {
                $q->whereIn('status', [TenantStatus::Active, TenantStatus::Trial]);
            });

        // Apply sorting
        $sortColumn = match ($this->sortBy) {
            'name' => null, // Will sort after query
            'sms_sent_this_month' => 'sms_sent_this_month',
            'donations_this_month' => 'donations_this_month',
            'attendance_this_month' => 'attendance_this_month',
            default => 'active_members',
        };

        if ($sortColumn) {
            $query->orderBy($sortColumn, $this->sortDirection);
        }

        $snapshots = $query->limit($this->topTenantsLimit)->get();

        // If sorting by tenant name, do it in memory
        if ($this->sortBy === 'name') {
            $snapshots = $this->sortDirection === 'asc'
                ? $snapshots->sortBy(fn ($s) => $s->tenant?->name)
                : $snapshots->sortByDesc(fn ($s) => $s->tenant?->name);
        }

        return $snapshots->map(function (TenantUsageSnapshot $snapshot) {
            return [
                'tenant' => $snapshot->tenant,
                'plan' => $snapshot->tenant?->subscriptionPlan,
                'active_members' => $snapshot->active_members,
                'sms_sent' => $snapshot->sms_sent_this_month,
                'donations' => Number::currency((float) $snapshot->donations_this_month, in: 'GHS'),
                'donationsRaw' => (float) $snapshot->donations_this_month,
                'attendance' => $snapshot->attendance_this_month,
            ];
        });
    }

    #[Computed]
    public function activityTrends(): array
    {
        // Get last 30 days of snapshots aggregated by date
        $startDate = now()->subDays(30)->toDateString();
        $endDate = now()->toDateString();

        $snapshots = TenantUsageSnapshot::query()
            ->selectRaw('snapshot_date, SUM(active_members) as total_members, SUM(sms_sent_this_month) as total_sms, SUM(donations_this_month) as total_donations')
            ->whereBetween('snapshot_date', [$startDate, $endDate])
            ->groupBy('snapshot_date')
            ->orderBy('snapshot_date')
            ->get();

        $labels = [];
        $membersData = [];
        $smsData = [];

        foreach ($snapshots as $snapshot) {
            $labels[] = $snapshot->snapshot_date->format('M d');
            $membersData[] = (int) $snapshot->total_members;
            $smsData[] = (int) $snapshot->total_sms;
        }

        return [
            'labels' => $labels,
            'members' => $membersData,
            'sms' => $smsData,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $stats = $this->overviewStats;
        $topTenants = $this->topTenants;

        $data = $topTenants->map(fn (array $item) => [
            'tenant_name' => $item['tenant']?->name ?? 'Unknown',
            'plan' => $item['plan']?->name ?? 'No Plan',
            'status' => $item['tenant']?->status?->value ?? 'Unknown',
            'active_members' => $item['active_members'],
            'sms_sent' => $item['sms_sent'],
            'donations' => $item['donations'],
            'attendance' => $item['attendance'],
        ]);

        $headers = [
            'Tenant Name',
            'Plan',
            'Status',
            'Active Members',
            'SMS Sent (Month)',
            'Donations (Month)',
            'Attendance (Month)',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_usage_analytics',
            description: 'Exported usage analytics report to CSV',
            metadata: [
                'total_tenants' => $stats['totalTenants'],
                'total_members' => $stats['totalMembers'],
                'total_sms' => $stats['totalSmsSent'],
            ],
        );

        $filename = 'usage-analytics-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function render(): View
    {
        return view('livewire.super-admin.analytics.usage-analytics')
            ->layout('components.layouts.superadmin.app');
    }
}
