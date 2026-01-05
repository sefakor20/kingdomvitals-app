<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Attendance;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class MonthlyAttendanceComparison extends Component
{
    use HasReportExport;

    public Branch $branch;

    #[Url]
    public int $months = 6;

    #[Url]
    public bool $showYoY = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function setMonths(int $months): void
    {
        $this->months = $months;
        unset($this->monthlyData, $this->chartData, $this->summaryStats);
    }

    public function toggleYoY(): void
    {
        $this->showYoY = ! $this->showYoY;
        unset($this->monthlyData, $this->chartData, $this->summaryStats, $this->yoyData);
    }

    #[Computed]
    public function monthlyData(): Collection
    {
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths($this->months - 1)->startOfMonth();

        $data = Attendance::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as total, COUNT(member_id) as members, COUNT(visitor_id) as visitors")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $item = $data->get($monthKey);

            $result->push([
                'month' => $current->format('M Y'),
                'short_month' => $current->format('M'),
                'month_key' => $monthKey,
                'total' => $item?->total ?? 0,
                'members' => $item?->members ?? 0,
                'visitors' => $item?->visitors ?? 0,
            ]);

            $current->addMonth();
        }

        return $result;
    }

    #[Computed]
    public function yoyData(): ?Collection
    {
        if (! $this->showYoY) {
            return null;
        }

        $endDate = now()->subYear()->endOfMonth();
        $startDate = now()->subYear()->subMonths($this->months - 1)->startOfMonth();

        $data = Attendance::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $item = $data->get($monthKey);

            $result->push([
                'month' => $current->format('M Y'),
                'short_month' => $current->format('M'),
                'total' => $item?->total ?? 0,
            ]);

            $current->addMonth();
        }

        return $result;
    }

    #[Computed]
    public function chartData(): array
    {
        $labels = $this->monthlyData->pluck('short_month')->toArray();
        $totals = $this->monthlyData->pluck('total')->toArray();

        $datasets = [
            [
                'label' => __('Current Year'),
                'data' => $totals,
                'backgroundColor' => 'rgb(59, 130, 246)',
                'borderRadius' => 4,
            ],
        ];

        if ($this->showYoY && $this->yoyData) {
            $datasets[] = [
                'label' => __('Previous Year'),
                'data' => $this->yoyData->pluck('total')->toArray(),
                'backgroundColor' => 'rgb(156, 163, 175)',
                'borderRadius' => 4,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    #[Computed]
    public function summaryStats(): array
    {
        $totalAttendance = $this->monthlyData->sum('total');
        $avgMonthly = $this->months > 0 ? round($totalAttendance / $this->months, 0) : 0;
        $highestMonth = $this->monthlyData->sortByDesc('total')->first();
        $lowestMonth = $this->monthlyData->where('total', '>', 0)->sortBy('total')->first();

        // Calculate trend (last month vs first month)
        $first = $this->monthlyData->first()['total'] ?? 0;
        $last = $this->monthlyData->last()['total'] ?? 0;
        $trend = $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0;

        return [
            'total' => $totalAttendance,
            'avg_monthly' => $avgMonthly,
            'highest' => $highestMonth,
            'lowest' => $lowestMonth,
            'trend' => $trend,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Month', 'Members', 'Visitors', 'Total'];
        $filename = $this->generateFilename('monthly-attendance', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Month', 'Members', 'Visitors', 'Total'];
        $filename = $this->generateFilename('monthly-attendance', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return $this->monthlyData->map(fn ($item) => [
            $item['month'],
            $item['members'],
            $item['visitors'],
            $item['total'],
        ]);
    }

    public function render()
    {
        return view('livewire.reports.attendance.monthly-attendance-comparison');
    }
}
