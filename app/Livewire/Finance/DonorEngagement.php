<?php

declare(strict_types=1);

namespace App\Livewire\Finance;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class DonorEngagement extends Component
{
    use WithPagination;

    public Branch $branch;

    #[Url]
    public int $period = 90;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    #[Url]
    public string $donorSearch = '';

    #[Url]
    public string $donorSortBy = 'lifetime_total';

    #[Url]
    public string $donorSortDirection = 'desc';

    #[Url]
    public string $donorTrendFilter = 'all';

    public int $lapseDaysThreshold = 90;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', [Donation::class, $branch]);
        $this->branch = $branch;
    }

    public function setPeriod(int $days): void
    {
        $this->period = $days;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function applyCustomDateRange(): void
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->period = 0;
            $this->resetPage();
        }
    }

    public function updatedDonorSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDonorTrendFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->donorSortBy === $column) {
            $this->donorSortDirection = $this->donorSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->donorSortBy = $column;
            $this->donorSortDirection = 'desc';
        }
        $this->resetPage();
    }

    // ============================================
    // PERIOD HELPERS
    // ============================================

    private function getCurrentPeriodStart(): Carbon
    {
        if ($this->dateFrom) {
            return Carbon::parse($this->dateFrom)->startOfDay();
        }

        return now()->subDays($this->period)->startOfDay();
    }

    private function getCurrentPeriodEnd(): Carbon
    {
        if ($this->dateTo) {
            return Carbon::parse($this->dateTo)->endOfDay();
        }

        return now()->endOfDay();
    }

    private function getPreviousPeriodStart(): Carbon
    {
        $periodLength = $this->getCurrentPeriodStart()->diffInDays($this->getCurrentPeriodEnd());

        return $this->getCurrentPeriodStart()->copy()->subDays($periodLength + 1)->startOfDay();
    }

    private function getPreviousPeriodEnd(): Carbon
    {
        return $this->getCurrentPeriodStart()->copy()->subDay()->endOfDay();
    }

    // ============================================
    // DONOR RETENTION METRICS
    // ============================================

    #[Computed]
    public function retentionMetrics(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();

        // Get current period donors
        $currentDonors = Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$currentStart, $currentEnd])
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->distinct()
            ->pluck('member_id');

        // Get previous period donors
        $previousDonors = Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$previousStart, $previousEnd])
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->distinct()
            ->pluck('member_id');

        // Calculate retention sets
        $returning = $currentDonors->intersect($previousDonors);
        $lapsed = $previousDonors->diff($currentDonors);
        $newThisPeriod = $currentDonors->diff($previousDonors);

        // Determine first-time ever donors vs reactivated
        $firstTimeEver = collect();
        $reactivated = collect();

        foreach ($newThisPeriod as $memberId) {
            $hasEarlierDonation = Donation::where('branch_id', $this->branch->id)
                ->where('member_id', $memberId)
                ->where('donation_date', '<', $previousStart)
                ->exists();

            if ($hasEarlierDonation) {
                $reactivated->push($memberId);
            } else {
                $firstTimeEver->push($memberId);
            }
        }

        // Calculate rates
        $totalPreviousDonors = $returning->count() + $lapsed->count();
        $retentionRate = $totalPreviousDonors > 0
            ? round(($returning->count() / $totalPreviousDonors) * 100, 1)
            : 0;
        $churnRate = $totalPreviousDonors > 0
            ? round(($lapsed->count() / $totalPreviousDonors) * 100, 1)
            : 0;

        return [
            'returning_donors' => $returning->count(),
            'lapsed_donors' => $lapsed->count(),
            'new_donors' => $firstTimeEver->count(),
            'reactivated_donors' => $reactivated->count(),
            'retention_rate' => $retentionRate,
            'churn_rate' => $churnRate,
            'current_period_total' => $currentDonors->count(),
            'previous_period_total' => $previousDonors->count(),
        ];
    }

    #[Computed]
    public function retentionTrendData(): array
    {
        $labels = [];
        $data = [];

        // Last 12 months retention rates
        for ($i = 11; $i >= 0; $i--) {
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthStart = now()->subMonths($i)->startOfMonth();
            $prevMonthEnd = now()->subMonths($i + 1)->endOfMonth();
            $prevMonthStart = now()->subMonths($i + 1)->startOfMonth();

            $labels[] = $monthStart->format('M');

            // Current month donors
            $currentDonors = Donation::where('branch_id', $this->branch->id)
                ->whereBetween('donation_date', [$monthStart, $monthEnd])
                ->whereNotNull('member_id')
                ->where('is_anonymous', false)
                ->distinct()
                ->pluck('member_id');

            // Previous month donors
            $previousDonors = Donation::where('branch_id', $this->branch->id)
                ->whereBetween('donation_date', [$prevMonthStart, $prevMonthEnd])
                ->whereNotNull('member_id')
                ->where('is_anonymous', false)
                ->distinct()
                ->pluck('member_id');

            $returning = $currentDonors->intersect($previousDonors)->count();
            $totalPrevious = $previousDonors->count();

            $rate = $totalPrevious > 0 ? round(($returning / $totalPrevious) * 100, 1) : 0;
            $data[] = $rate;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    // ============================================
    // GIVING TREND ANALYSIS
    // ============================================

    #[Computed]
    public function givingTrends(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();

        // Get donation totals for donors who gave in both periods
        $donorTotals = Donation::where('branch_id', $this->branch->id)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->whereBetween('donation_date', [$previousStart, $currentEnd])
            ->selectRaw('
                member_id,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as current_total,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as previous_total
            ', [$currentStart, $currentEnd, $previousStart, $previousEnd])
            ->groupBy('member_id')
            ->having('previous_total', '>', 0)
            ->having('current_total', '>', 0)
            ->get();

        $increasing = collect();
        $declining = collect();
        $consistent = collect();

        foreach ($donorTotals as $donor) {
            $currentTotal = (float) $donor->current_total;
            $previousTotal = (float) $donor->previous_total;
            $changePercent = (($currentTotal - $previousTotal) / $previousTotal) * 100;

            if ($changePercent > 25) {
                $increasing->push([
                    'member_id' => $donor->member_id,
                    'current_total' => $currentTotal,
                    'previous_total' => $previousTotal,
                    'change_percent' => round($changePercent, 1),
                ]);
            } elseif ($changePercent < -25) {
                $declining->push([
                    'member_id' => $donor->member_id,
                    'current_total' => $currentTotal,
                    'previous_total' => $previousTotal,
                    'change_percent' => round($changePercent, 1),
                ]);
            } else {
                $consistent->push([
                    'member_id' => $donor->member_id,
                    'current_total' => $currentTotal,
                    'previous_total' => $previousTotal,
                    'change_percent' => round($changePercent, 1),
                ]);
            }
        }

        return [
            'increasing_count' => $increasing->count(),
            'increasing_total' => $increasing->sum('current_total'),
            'increasing_change' => $increasing->count() > 0 ? round($increasing->avg('change_percent'), 1) : 0,
            'declining_count' => $declining->count(),
            'declining_total' => $declining->sum('current_total'),
            'declining_change' => $declining->count() > 0 ? round($declining->avg('change_percent'), 1) : 0,
            'consistent_count' => $consistent->count(),
            'consistent_total' => $consistent->sum('current_total'),
        ];
    }

    // ============================================
    // DONOR SEGMENTS
    // ============================================

    #[Computed]
    public function donorSegments(): array
    {
        $currentYear = now()->year;

        // Get all donors with their YTD totals and donation counts
        $donors = Donation::where('branch_id', $this->branch->id)
            ->whereYear('donation_date', $currentYear)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->selectRaw('member_id, SUM(amount) as total_amount, COUNT(*) as donation_count')
            ->groupBy('member_id')
            ->orderByDesc('total_amount')
            ->get();

        $totalDonors = $donors->count();

        if ($totalDonors === 0) {
            return [
                'major' => ['count' => 0, 'total' => 0, 'avg' => 0],
                'regular' => ['count' => 0, 'total' => 0, 'avg' => 0],
                'occasional' => ['count' => 0, 'total' => 0, 'avg' => 0],
                'chart_data' => [
                    'labels' => ['Major Donors', 'Regular Donors', 'Occasional Donors'],
                    'data' => [0, 0, 0],
                    'colors' => ['#8b5cf6', '#3b82f6', '#71717a'],
                ],
            ];
        }

        // Major donors: Top 10% by total giving
        $top10Threshold = max(1, (int) ceil($totalDonors * 0.1));
        $majorDonors = $donors->take($top10Threshold);

        // Occasional donors: 1-2 donations per year
        $occasionalDonors = $donors->filter(fn ($d): bool => $d->donation_count <= 2);

        // Regular donors: everyone else (not major, more than 2 donations)
        $regularDonors = $donors->skip($top10Threshold)->filter(fn ($d): bool => $d->donation_count > 2);

        return [
            'major' => [
                'count' => $majorDonors->count(),
                'total' => (float) $majorDonors->sum('total_amount'),
                'avg' => $majorDonors->count() > 0 ? round($majorDonors->avg('total_amount'), 2) : 0,
            ],
            'regular' => [
                'count' => $regularDonors->count(),
                'total' => (float) $regularDonors->sum('total_amount'),
                'avg' => $regularDonors->count() > 0 ? round($regularDonors->avg('total_amount'), 2) : 0,
            ],
            'occasional' => [
                'count' => $occasionalDonors->count(),
                'total' => (float) $occasionalDonors->sum('total_amount'),
                'avg' => $occasionalDonors->count() > 0 ? round($occasionalDonors->avg('total_amount'), 2) : 0,
            ],
            'chart_data' => [
                'labels' => ['Major Donors', 'Regular Donors', 'Occasional Donors'],
                'data' => [
                    (float) $majorDonors->sum('total_amount'),
                    (float) $regularDonors->sum('total_amount'),
                    (float) $occasionalDonors->sum('total_amount'),
                ],
                'colors' => ['#8b5cf6', '#3b82f6', '#71717a'],
            ],
        ];
    }

    // ============================================
    // INDIVIDUAL DONOR LIST
    // ============================================

    #[Computed]
    public function donorsList(): LengthAwarePaginator
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();

        // Build the donor data query
        $donorsQuery = Donation::where('donations.branch_id', $this->branch->id)
            ->whereNotNull('donations.member_id')
            ->where('donations.is_anonymous', false)
            ->join('members', 'donations.member_id', '=', 'members.id')
            ->selectRaw('
                donations.member_id,
                members.first_name,
                members.last_name,
                SUM(donations.amount) as lifetime_total,
                COUNT(donations.id) as total_donations,
                AVG(donations.amount) as average_donation,
                MAX(donations.donation_date) as last_donation_date,
                MIN(donations.donation_date) as first_donation_date,
                SUM(CASE WHEN donations.donation_date BETWEEN ? AND ? THEN donations.amount ELSE 0 END) as period_total,
                SUM(CASE WHEN donations.donation_date BETWEEN ? AND ? THEN donations.amount ELSE 0 END) as previous_period_total
            ', [$currentStart, $currentEnd, $previousStart, $previousEnd])
            ->groupBy('donations.member_id', 'members.first_name', 'members.last_name');

        // Apply search filter
        if ($this->donorSearch !== '' && $this->donorSearch !== '0') {
            $donorsQuery->where(function ($q): void {
                $q->where('members.first_name', 'like', '%'.$this->donorSearch.'%')
                    ->orWhere('members.last_name', 'like', '%'.$this->donorSearch.'%');
            });
        }

        // Get raw results to filter by trend
        $allDonors = $donorsQuery->get()->map(function ($donor) {
            $periodTotal = (float) $donor->period_total;
            $previousTotal = (float) $donor->previous_period_total;

            // Determine trend
            if ($previousTotal > 0 && $periodTotal > 0) {
                $changePercent = (($periodTotal - $previousTotal) / $previousTotal) * 100;
                if ($changePercent > 25) {
                    $trend = 'increasing';
                } elseif ($changePercent < -25) {
                    $trend = 'declining';
                } else {
                    $trend = 'consistent';
                }
            } elseif ($periodTotal > 0 && $previousTotal == 0) {
                $trend = 'new';
            } elseif ($periodTotal == 0 && $previousTotal > 0) {
                $trend = 'lapsed';
            } else {
                $trend = 'inactive';
            }

            $donor->trend = $trend;
            $donor->days_since_last = (int) Carbon::parse($donor->last_donation_date)->diffInDays(now());

            return $donor;
        });

        // Apply trend filter
        if ($this->donorTrendFilter !== 'all') {
            $allDonors = $allDonors->filter(fn ($d): bool => $d->trend === $this->donorTrendFilter);
        }

        // Apply sorting
        $sorted = match ($this->donorSortBy) {
            'lifetime_total' => $allDonors->sortBy('lifetime_total', SORT_REGULAR, $this->donorSortDirection === 'desc'),
            'period_total' => $allDonors->sortBy('period_total', SORT_REGULAR, $this->donorSortDirection === 'desc'),
            'last_donation_date' => $allDonors->sortBy('last_donation_date', SORT_REGULAR, $this->donorSortDirection === 'desc'),
            'average_donation' => $allDonors->sortBy('average_donation', SORT_REGULAR, $this->donorSortDirection === 'desc'),
            default => $allDonors->sortBy('lifetime_total', SORT_REGULAR, true),
        };

        // Manual pagination
        $page = $this->getPage();
        $perPage = 10;
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // ============================================
    // ENGAGEMENT ALERTS
    // ============================================

    #[Computed]
    public function engagementAlerts(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();
        $lapseDate = now()->subDays($this->lapseDaysThreshold);

        // Get current year major donors (top 10%)
        $currentYear = now()->year;
        $allYearDonors = Donation::where('branch_id', $this->branch->id)
            ->whereYear('donation_date', $currentYear)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->selectRaw('member_id, SUM(amount) as total_amount')
            ->groupBy('member_id')
            ->orderByDesc('total_amount')
            ->get();

        $top10Threshold = max(1, (int) ceil($allYearDonors->count() * 0.1));
        $majorDonorIds = $allYearDonors->take($top10Threshold)->pluck('member_id');

        // Lapsing donors: No donation in X days
        $lapsingDonors = Member::where('primary_branch_id', $this->branch->id)
            ->whereHas('donations', function ($q) use ($lapseDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('donation_date', '<', $lapseDate);
            })
            ->whereDoesntHave('donations', function ($q) use ($lapseDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('donation_date', '>=', $lapseDate);
            })
            ->with(['donations' => function ($q): void {
                $q->where('branch_id', $this->branch->id)
                    ->orderByDesc('donation_date')
                    ->limit(1);
            }])
            ->limit(10)
            ->get();

        // Significantly declining donors (>50% drop)
        $decliningDonors = Donation::where('branch_id', $this->branch->id)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->whereBetween('donation_date', [$previousStart, $currentEnd])
            ->selectRaw('
                member_id,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as current_total,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as previous_total
            ', [$currentStart, $currentEnd, $previousStart, $previousEnd])
            ->groupBy('member_id')
            ->havingRaw('previous_total > 0 AND current_total < previous_total * 0.5')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->member = Member::find($item->member_id);
                $item->change_percent = round((($item->current_total - $item->previous_total) / $item->previous_total) * 100, 1);

                return $item;
            });

        // At-risk major donors (major donor with declining trend)
        $atRiskMajorDonors = Donation::where('branch_id', $this->branch->id)
            ->whereIn('member_id', $majorDonorIds)
            ->whereBetween('donation_date', [$previousStart, $currentEnd])
            ->selectRaw('
                member_id,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as current_total,
                SUM(CASE WHEN donation_date BETWEEN ? AND ? THEN amount ELSE 0 END) as previous_total
            ', [$currentStart, $currentEnd, $previousStart, $previousEnd])
            ->groupBy('member_id')
            ->havingRaw('previous_total > 0 AND current_total < previous_total * 0.75')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->member = Member::find($item->member_id);
                $item->change_percent = round((($item->current_total - $item->previous_total) / $item->previous_total) * 100, 1);

                return $item;
            });

        // New potential major donors (new donor with high initial giving)
        $avgDonation = Donation::where('branch_id', $this->branch->id)
            ->whereYear('donation_date', $currentYear)
            ->avg('amount') ?? 0;

        $highThreshold = $avgDonation * 3; // 3x average = potential major

        $potentialMajorDonors = Donation::where('branch_id', $this->branch->id)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->whereBetween('donation_date', [$currentStart, $currentEnd])
            ->selectRaw('member_id, SUM(amount) as period_total, COUNT(*) as donation_count')
            ->groupBy('member_id')
            ->having('period_total', '>=', $highThreshold)
            ->whereNotExists(function ($q) use ($currentStart): void {
                $q->select(DB::raw(1))
                    ->from('donations as d2')
                    ->whereColumn('d2.member_id', 'donations.member_id')
                    ->where('d2.branch_id', $this->branch->id)
                    ->where('d2.donation_date', '<', $currentStart);
            })
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->member = Member::find($item->member_id);

                return $item;
            });

        return [
            'lapsing' => $lapsingDonors,
            'declining' => $decliningDonors,
            'at_risk_major' => $atRiskMajorDonors,
            'potential_major' => $potentialMajorDonors,
            'lapsing_count' => $lapsingDonors->count(),
            'declining_count' => $decliningDonors->count(),
            'at_risk_count' => $atRiskMajorDonors->count(),
            'potential_count' => $potentialMajorDonors->count(),
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.finance.donor-engagement');
    }
}
