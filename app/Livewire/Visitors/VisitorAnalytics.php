<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Enums\VisitorStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class VisitorAnalytics extends Component
{
    use AuthorizesRequests;

    public Branch $branch;

    #[Url]
    public int $period = 90;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Visitor::class, $branch]);
        $this->branch = $branch;
    }

    public function setPeriod(int $days): void
    {
        $this->period = $days;
        $this->clearComputedCache();

        $this->dispatch('charts-updated', [
            'visitorsOverTime' => $this->visitorsOverTimeData,
            'followUpEffectiveness' => $this->followUpEffectivenessData,
            'statusDistribution' => $this->conversionFunnelData,
            'followUpTrend' => $this->followUpTrendData,
            'visitorSource' => $this->visitorSourceData,
        ]);
    }

    // ============================================
    // PERIOD HELPERS
    // ============================================

    private function getCurrentPeriodStart(): Carbon
    {
        return now()->subDays($this->period)->startOfDay();
    }

    private function getCurrentPeriodEnd(): Carbon
    {
        return now()->endOfDay();
    }

    private function getPreviousPeriodStart(): Carbon
    {
        return $this->getCurrentPeriodStart()->copy()->subDays($this->period)->startOfDay();
    }

    private function getPreviousPeriodEnd(): Carbon
    {
        return $this->getCurrentPeriodStart()->copy()->subDay()->endOfDay();
    }

    /**
     * Determine the chart grouping strategy based on the selected period.
     *
     * @return array{type: string, format: string, sql_group: string, sql_format: string}
     */
    private function getChartGroupingStrategy(): array
    {
        return match (true) {
            $this->period <= 30 => [
                'type' => 'daily',
                'format' => 'M d',
                'sql_group' => 'DATE(visit_date)',
                'sql_format' => '%Y-%m-%d',
            ],
            $this->period <= 180 => [
                'type' => 'weekly',
                'format' => 'M d',
                'sql_group' => 'YEARWEEK(visit_date, 1)',
                'sql_format' => '%x%v',
            ],
            default => [
                'type' => 'monthly',
                'format' => 'M Y',
                'sql_group' => "DATE_FORMAT(visit_date, '%Y-%m')",
                'sql_format' => '%Y-%m',
            ],
        };
    }

    // ============================================
    // SUMMARY STATS
    // ============================================

    #[Computed]
    public function summaryStats(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();

        // Current period stats
        $totalVisitors = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$currentStart, $currentEnd])
            ->count();

        $convertedVisitors = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$currentStart, $currentEnd])
            ->where('status', VisitorStatus::Converted)
            ->count();

        $conversionRate = $totalVisitors > 0
            ? round(($convertedVisitors / $totalVisitors) * 100, 1)
            : 0;

        // Follow-up success rate
        $totalFollowUps = VisitorFollowUp::whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->whereBetween('completed_at', [$currentStart, $currentEnd])
            ->where('outcome', '!=', FollowUpOutcome::Pending)
            ->count();

        $successfulFollowUps = VisitorFollowUp::whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->whereBetween('completed_at', [$currentStart, $currentEnd])
            ->where('outcome', FollowUpOutcome::Successful)
            ->count();

        $followUpSuccessRate = $totalFollowUps > 0
            ? round(($successfulFollowUps / $totalFollowUps) * 100, 1)
            : 0;

        // Average days to convert
        $convertedWithDates = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$currentStart, $currentEnd])
            ->where('status', VisitorStatus::Converted)
            ->whereNotNull('updated_at')
            ->get();

        $avgDaysToConvert = 0;
        if ($convertedWithDates->isNotEmpty()) {
            $totalDays = $convertedWithDates->sum(function ($visitor) {
                return $visitor->visit_date->diffInDays($visitor->updated_at);
            });
            $avgDaysToConvert = round($totalDays / $convertedWithDates->count(), 1);
        }

        // Previous period for growth calculation
        $previousTotal = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$previousStart, $previousEnd])
            ->count();

        $visitorGrowth = $previousTotal > 0
            ? round((($totalVisitors - $previousTotal) / $previousTotal) * 100, 1)
            : ($totalVisitors > 0 ? 100 : 0);

        return [
            'total_visitors' => $totalVisitors,
            'previous_total' => $previousTotal,
            'visitor_growth' => $visitorGrowth,
            'converted_visitors' => $convertedVisitors,
            'conversion_rate' => $conversionRate,
            'total_follow_ups' => $totalFollowUps,
            'successful_follow_ups' => $successfulFollowUps,
            'follow_up_success_rate' => $followUpSuccessRate,
            'avg_days_to_convert' => $avgDaysToConvert,
        ];
    }

    // ============================================
    // CONVERSION FUNNEL DATA
    // ============================================

    #[Computed]
    public function conversionFunnelData(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        $baseQuery = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$currentStart, $currentEnd]);

        $total = (clone $baseQuery)->count();

        $newCount = (clone $baseQuery)->where('status', VisitorStatus::New)->count();
        $followedUpCount = (clone $baseQuery)->where('status', VisitorStatus::FollowedUp)->count();
        $returningCount = (clone $baseQuery)->where('status', VisitorStatus::Returning)->count();
        $convertedCount = (clone $baseQuery)->where('status', VisitorStatus::Converted)->count();
        $notInterestedCount = (clone $baseQuery)->where('status', VisitorStatus::NotInterested)->count();

        return [
            'labels' => ['New', 'Followed Up', 'Returning', 'Converted', 'Not Interested'],
            'data' => [$newCount, $followedUpCount, $returningCount, $convertedCount, $notInterestedCount],
            'colors' => ['#3b82f6', '#f59e0b', '#22c55e', '#8b5cf6', '#ef4444'],
            'total' => $total,
        ];
    }

    // ============================================
    // FOLLOW-UP EFFECTIVENESS BY TYPE
    // ============================================

    #[Computed]
    public function followUpEffectivenessData(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        $labels = [];
        $totalAttempts = [];
        $successfulCounts = [];
        $successRates = [];

        foreach (FollowUpType::cases() as $type) {
            $baseQuery = VisitorFollowUp::whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
                ->where('type', $type)
                ->whereBetween('completed_at', [$currentStart, $currentEnd])
                ->where('outcome', '!=', FollowUpOutcome::Pending);

            $total = (clone $baseQuery)->count();
            $successful = (clone $baseQuery)->where('outcome', FollowUpOutcome::Successful)->count();

            $labels[] = ucfirst($type->value);
            $totalAttempts[] = $total;
            $successfulCounts[] = $successful;
            $successRates[] = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        }

        return [
            'labels' => $labels,
            'total_attempts' => $totalAttempts,
            'successful' => $successfulCounts,
            'success_rates' => $successRates,
        ];
    }

    // ============================================
    // VISITOR SOURCE ANALYSIS
    // ============================================

    #[Computed]
    public function visitorSourceData(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        $sources = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$currentStart, $currentEnd])
            ->selectRaw('how_did_you_hear, COUNT(*) as visitor_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as converted_count', [VisitorStatus::Converted->value])
            ->groupBy('how_did_you_hear')
            ->orderByDesc('visitor_count')
            ->get();

        $labels = [];
        $visitorCounts = [];
        $convertedCounts = [];
        $conversionRates = [];
        $tableData = [];

        foreach ($sources as $source) {
            $sourceName = $source->how_did_you_hear ?: 'Unknown';
            $rate = $source->visitor_count > 0
                ? round(($source->converted_count / $source->visitor_count) * 100, 1)
                : 0;

            $labels[] = $sourceName;
            $visitorCounts[] = $source->visitor_count;
            $convertedCounts[] = $source->converted_count;
            $conversionRates[] = $rate;

            $tableData[] = [
                'source' => $sourceName,
                'visitors' => $source->visitor_count,
                'converted' => $source->converted_count,
                'rate' => $rate,
            ];
        }

        return [
            'labels' => $labels,
            'visitor_counts' => $visitorCounts,
            'converted_counts' => $convertedCounts,
            'conversion_rates' => $conversionRates,
            'table_data' => $tableData,
        ];
    }

    // ============================================
    // VISITORS OVER TIME
    // ============================================

    #[Computed]
    public function visitorsOverTimeData(): array
    {
        $strategy = $this->getChartGroupingStrategy();
        $startDate = $this->getCurrentPeriodStart();
        $endDate = $this->getCurrentPeriodEnd();

        $groupedData = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw("
                {$strategy['sql_group']} as date_key,
                COUNT(*) as visitor_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as converted_count
            ", [VisitorStatus::Converted->value])
            ->groupBy('date_key')
            ->get()
            ->keyBy('date_key');

        $labels = [];
        $visitorData = [];
        $convertedData = [];

        // Build arrays based on grouping strategy
        if ($strategy['type'] === 'daily') {
            for ($i = $this->period - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateKey = $date->format('Y-m-d');

                $labels[] = $date->format($strategy['format']);
                $data = $groupedData->get($dateKey);
                $visitorData[] = $data ? (int) $data->visitor_count : 0;
                $convertedData[] = $data ? (int) $data->converted_count : 0;
            }
        } elseif ($strategy['type'] === 'weekly') {
            $weeks = (int) ceil($this->period / 7);
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $weekStart = now()->subWeeks($i)->startOfWeek();
                $weekKey = $weekStart->format('oW'); // ISO year and week number

                $labels[] = $weekStart->format($strategy['format']);
                $data = $groupedData->get($weekKey);
                $visitorData[] = $data ? (int) $data->visitor_count : 0;
                $convertedData[] = $data ? (int) $data->converted_count : 0;
            }
        } else {
            // Monthly grouping
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthKey = $monthStart->format('Y-m');

                $labels[] = $monthStart->format($strategy['format']);
                $data = $groupedData->get($monthKey);
                $visitorData[] = $data ? (int) $data->visitor_count : 0;
                $convertedData[] = $data ? (int) $data->converted_count : 0;
            }
        }

        return [
            'labels' => $labels,
            'visitors' => $visitorData,
            'converted' => $convertedData,
        ];
    }

    // ============================================
    // FOLLOW-UP TREND DATA
    // ============================================

    #[Computed]
    public function followUpTrendData(): array
    {
        $strategy = $this->getChartGroupingStrategy();
        $startDate = $this->getCurrentPeriodStart();
        $endDate = $this->getCurrentPeriodEnd();

        // Build SQL grouping for follow-up dates (uses completed_at/scheduled_at instead of visit_date)
        $completedSqlGroup = str_replace('visit_date', 'completed_at', $strategy['sql_group']);
        $scheduledSqlGroup = str_replace('visit_date', 'scheduled_at', $strategy['sql_group']);

        $completedGroupedData = VisitorFollowUp::whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->where('outcome', '!=', FollowUpOutcome::Pending)
            ->selectRaw("{$completedSqlGroup} as date_key, COUNT(*) as count")
            ->groupBy('date_key')
            ->pluck('count', 'date_key');

        $pendingGroupedData = VisitorFollowUp::whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->where('outcome', FollowUpOutcome::Pending)
            ->selectRaw("{$scheduledSqlGroup} as date_key, COUNT(*) as count")
            ->groupBy('date_key')
            ->pluck('count', 'date_key');

        $labels = [];
        $completedData = [];
        $pendingData = [];

        // Build arrays based on grouping strategy
        if ($strategy['type'] === 'daily') {
            for ($i = $this->period - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateKey = $date->format('Y-m-d');

                $labels[] = $date->format($strategy['format']);
                $completedData[] = (int) ($completedGroupedData->get($dateKey) ?? 0);
                $pendingData[] = (int) ($pendingGroupedData->get($dateKey) ?? 0);
            }
        } elseif ($strategy['type'] === 'weekly') {
            $weeks = (int) ceil($this->period / 7);
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $weekStart = now()->subWeeks($i)->startOfWeek();
                $weekKey = $weekStart->format('oW');

                $labels[] = $weekStart->format($strategy['format']);
                $completedData[] = (int) ($completedGroupedData->get($weekKey) ?? 0);
                $pendingData[] = (int) ($pendingGroupedData->get($weekKey) ?? 0);
            }
        } else {
            // Monthly grouping
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthKey = $monthStart->format('Y-m');

                $labels[] = $monthStart->format($strategy['format']);
                $completedData[] = (int) ($completedGroupedData->get($monthKey) ?? 0);
                $pendingData[] = (int) ($pendingGroupedData->get($monthKey) ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'completed' => $completedData,
            'pending' => $pendingData,
        ];
    }

    // ============================================
    // RECENT CONVERSIONS
    // ============================================

    #[Computed]
    public function recentConversions(): \Illuminate\Support\Collection
    {
        return Visitor::where('branch_id', $this->branch->id)
            ->where('status', VisitorStatus::Converted)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($visitor): array => [
                'id' => $visitor->id,
                'name' => $visitor->fullName(),
                'visit_date' => $visitor->visit_date?->format('M d, Y'),
                'converted_at' => $visitor->updated_at?->format('M d, Y'),
                'days_to_convert' => $visitor->visit_date?->diffInDays($visitor->updated_at),
                'source' => $visitor->how_did_you_hear,
            ]);
    }

    private function clearComputedCache(): void
    {
        unset(
            $this->summaryStats,
            $this->conversionFunnelData,
            $this->followUpEffectivenessData,
            $this->visitorSourceData,
            $this->visitorsOverTimeData,
            $this->followUpTrendData,
            $this->recentConversions
        );
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.visitors.visitor-analytics');
    }
}
