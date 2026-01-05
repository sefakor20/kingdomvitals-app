<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Membership;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class MemberGrowthTrends extends Component
{
    use HasReportExport;

    public Branch $branch;

    #[Url]
    public int $months = 12;

    #[Url]
    public bool $showComparison = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function setMonths(int $months): void
    {
        $this->months = $months;
        unset($this->growthData, $this->chartData, $this->summaryStats);
    }

    public function toggleComparison(): void
    {
        $this->showComparison = ! $this->showComparison;
    }

    #[Computed]
    public function growthData(): Collection
    {
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths($this->months - 1)->startOfMonth();

        $monthlyData = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereNotNull('joined_at')
            ->whereBetween('joined_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(joined_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $data = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $data->put($monthKey, [
                'month' => $current->format('M Y'),
                'short_month' => $current->format('M'),
                'new_members' => $monthlyData->get($monthKey, 0),
                'cumulative' => $this->getCumulativeMembersAt($current->copy()->endOfMonth()),
            ]);
            $current->addMonth();
        }

        return $data;
    }

    protected function getCumulativeMembersAt($date): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->where(function ($query) use ($date) {
                $query->whereNull('joined_at')
                    ->orWhere('joined_at', '<=', $date);
            })
            ->count();
    }

    #[Computed]
    public function comparisonData(): ?Collection
    {
        if (! $this->showComparison) {
            return null;
        }

        $endDate = now()->subYear()->endOfMonth();
        $startDate = now()->subYear()->subMonths($this->months - 1)->startOfMonth();

        $monthlyData = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereNotNull('joined_at')
            ->whereBetween('joined_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(joined_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $data = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $data->put($monthKey, [
                'month' => $current->format('M Y'),
                'short_month' => $current->format('M'),
                'new_members' => $monthlyData->get($monthKey, 0),
            ]);
            $current->addMonth();
        }

        return $data;
    }

    #[Computed]
    public function chartData(): array
    {
        $labels = $this->growthData->pluck('short_month')->values()->toArray();
        $currentData = $this->growthData->pluck('new_members')->values()->toArray();

        $datasets = [
            [
                'label' => __('New Members (Current)'),
                'data' => $currentData,
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ],
        ];

        if ($this->showComparison && $this->comparisonData) {
            $datasets[] = [
                'label' => __('New Members (Previous Year)'),
                'data' => $this->comparisonData->pluck('new_members')->values()->toArray(),
                'borderColor' => 'rgb(156, 163, 175)',
                'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                'fill' => false,
                'tension' => 0.3,
                'borderDash' => [5, 5],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    #[Computed]
    public function cumulativeChartData(): array
    {
        return [
            'labels' => $this->growthData->pluck('short_month')->values()->toArray(),
            'data' => $this->growthData->pluck('cumulative')->values()->toArray(),
        ];
    }

    #[Computed]
    public function summaryStats(): array
    {
        $totalNew = $this->growthData->sum('new_members');
        $avgPerMonth = $this->months > 0 ? round($totalNew / $this->months, 1) : 0;

        $currentMembers = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->count();

        // Calculate growth rate
        $firstMonthCumulative = $this->growthData->first()['cumulative'] ?? 0;
        $lastMonthCumulative = $this->growthData->last()['cumulative'] ?? 0;
        $growthRate = $firstMonthCumulative > 0
            ? round((($lastMonthCumulative - $firstMonthCumulative) / $firstMonthCumulative) * 100, 1)
            : 0;

        // YoY comparison if enabled
        $yoyChange = null;
        if ($this->showComparison && $this->comparisonData) {
            $previousTotal = $this->comparisonData->sum('new_members');
            if ($previousTotal > 0) {
                $yoyChange = round((($totalNew - $previousTotal) / $previousTotal) * 100, 1);
            }
        }

        return [
            'total_new' => $totalNew,
            'avg_per_month' => $avgPerMonth,
            'current_members' => $currentMembers,
            'growth_rate' => $growthRate,
            'yoy_change' => $yoyChange,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Month', 'New Members', 'Cumulative Total'];
        $filename = $this->generateFilename('growth-trends', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Month', 'New Members', 'Cumulative Total'];
        $filename = $this->generateFilename('growth-trends', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return $this->growthData->map(fn ($item) => [
            $item['month'],
            $item['new_members'],
            $item['cumulative'],
        ])->values();
    }

    public function render()
    {
        return view('livewire.reports.membership.member-growth-trends');
    }
}
