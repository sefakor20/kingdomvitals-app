<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Attendance;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class WeeklyAttendanceSummary extends Component
{
    use HasReportExport;

    public Branch $branch;

    #[Url]
    public string $weekStart = '';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;

        if ($this->weekStart === '') {
            $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        }
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
        unset($this->dailyBreakdown, $this->weeklyTotals, $this->chartData);
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
        unset($this->dailyBreakdown, $this->weeklyTotals, $this->chartData);
    }

    public function goToCurrentWeek(): void
    {
        $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        unset($this->dailyBreakdown, $this->weeklyTotals, $this->chartData);
    }

    #[Computed]
    public function weekEnd(): string
    {
        return Carbon::parse($this->weekStart)->endOfWeek()->format('Y-m-d');
    }

    #[Computed]
    public function weekLabel(): string
    {
        $start = Carbon::parse($this->weekStart);
        $end = Carbon::parse($this->weekEnd);

        return $start->format('M d').' - '.$end->format('M d, Y');
    }

    #[Computed]
    public function dailyBreakdown(): Collection
    {
        $start = Carbon::parse($this->weekStart);
        $end = Carbon::parse($this->weekEnd);

        $attendanceData = Attendance::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('date, COUNT(*) as total, COUNT(member_id) as members, COUNT(visitor_id) as visitors')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $days = collect();
        $current = $start->copy();

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $data = $attendanceData->get($dateKey);

            $days->push([
                'date' => $current->format('Y-m-d'),
                'day_name' => $current->format('l'),
                'short_date' => $current->format('M d'),
                'total' => $data?->total ?? 0,
                'members' => $data?->members ?? 0,
                'visitors' => $data?->visitors ?? 0,
            ]);

            $current->addDay();
        }

        return $days;
    }

    #[Computed]
    public function weeklyTotals(): array
    {
        $totals = $this->dailyBreakdown->reduce(function ($carry, $day) {
            $carry['total'] += $day['total'];
            $carry['members'] += $day['members'];
            $carry['visitors'] += $day['visitors'];

            return $carry;
        }, ['total' => 0, 'members' => 0, 'visitors' => 0]);

        $totals['daily_average'] = round($totals['total'] / 7, 1);

        // Get previous week comparison
        $prevStart = Carbon::parse($this->weekStart)->subWeek();
        $prevEnd = Carbon::parse($this->weekEnd)->subWeek();

        $previousTotal = Attendance::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->count();

        $totals['previous_total'] = $previousTotal;
        $totals['change'] = $previousTotal > 0
            ? round((($totals['total'] - $previousTotal) / $previousTotal) * 100, 1)
            : ($totals['total'] > 0 ? 100 : 0);

        return $totals;
    }

    #[Computed]
    public function chartData(): array
    {
        return [
            'labels' => $this->dailyBreakdown->pluck('day_name')->toArray(),
            'members' => $this->dailyBreakdown->pluck('members')->toArray(),
            'visitors' => $this->dailyBreakdown->pluck('visitors')->toArray(),
        ];
    }

    #[Computed]
    public function serviceBreakdown(): Collection
    {
        return Attendance::query()
            ->where('attendance.branch_id', $this->branch->id)
            ->whereBetween('date', [$this->weekStart, $this->weekEnd])
            ->whereNotNull('service_id')
            ->join('services', 'attendance.service_id', '=', 'services.id')
            ->selectRaw('services.name, services.service_type, COUNT(*) as attendance_count')
            ->groupBy('services.id', 'services.name', 'services.service_type')
            ->orderByDesc('attendance_count')
            ->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Date', 'Day', 'Members', 'Visitors', 'Total'];
        $filename = $this->generateFilename('weekly-attendance', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Date', 'Day', 'Members', 'Visitors', 'Total'];
        $filename = $this->generateFilename('weekly-attendance', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return $this->dailyBreakdown->map(fn ($day) => [
            $day['date'],
            $day['day_name'],
            $day['members'],
            $day['visitors'],
            $day['total'],
        ]);
    }

    public function render()
    {
        return view('livewire.reports.attendance.weekly-attendance-summary');
    }
}
