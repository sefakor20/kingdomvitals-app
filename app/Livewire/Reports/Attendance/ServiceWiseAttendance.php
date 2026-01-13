<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Attendance;

use App\Livewire\Concerns\HasReportExport;
use App\Livewire\Concerns\HasReportFilters;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class ServiceWiseAttendance extends Component
{
    use HasReportExport, HasReportFilters;

    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    protected function clearReportCaches(): void
    {
        unset($this->serviceData, $this->chartData, $this->summaryStats, $this->trendData);
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function serviceData(): Collection
    {
        return Attendance::query()
            ->where('attendance.branch_id', $this->branch->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereNotNull('service_id')
            ->join('services', 'attendance.service_id', '=', 'services.id')
            ->selectRaw('
                services.id,
                services.name,
                services.service_type,
                COUNT(*) as total_attendance,
                COUNT(DISTINCT attendance.member_id) as unique_members,
                COUNT(DISTINCT attendance.visitor_id) as unique_visitors,
                COUNT(DISTINCT date) as service_days
            ')
            ->groupBy('services.id', 'services.name', 'services.service_type')
            ->orderByDesc('total_attendance')
            ->get()
            ->map(function ($service): \stdClass {
                $service->avg_per_service = $service->service_days > 0
                    ? round($service->total_attendance / $service->service_days, 1)
                    : 0;

                return $service;
            });
    }

    #[Computed]
    public function chartData(): array
    {
        $colors = [
            'rgb(59, 130, 246)',
            'rgb(34, 197, 94)',
            'rgb(168, 85, 247)',
            'rgb(249, 115, 22)',
            'rgb(236, 72, 153)',
            'rgb(20, 184, 166)',
            'rgb(245, 158, 11)',
            'rgb(107, 114, 128)',
        ];

        return [
            'labels' => $this->serviceData->pluck('name')->toArray(),
            'data' => $this->serviceData->pluck('total_attendance')->toArray(),
            'colors' => array_slice($colors, 0, $this->serviceData->count()),
        ];
    }

    #[Computed]
    public function trendData(): array
    {
        $trendData = Attendance::query()
            ->where('attendance.branch_id', $this->branch->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereNotNull('service_id')
            ->join('services', 'attendance.service_id', '=', 'services.id')
            ->selectRaw("DATE_FORMAT(date, '%Y-%m-%d') as date, services.name, COUNT(*) as count")
            ->groupBy('date', 'services.id', 'services.name')
            ->orderBy('date')
            ->get();

        $dates = $trendData->pluck('date')->unique()->sort()->values();
        $serviceNames = $this->serviceData->pluck('name')->toArray();

        $datasets = [];
        $colors = [
            'rgb(59, 130, 246)',
            'rgb(34, 197, 94)',
            'rgb(168, 85, 247)',
            'rgb(249, 115, 22)',
            'rgb(236, 72, 153)',
        ];

        foreach ($serviceNames as $index => $serviceName) {
            $data = [];
            foreach ($dates as $date) {
                $count = $trendData->where('date', $date)->where('name', $serviceName)->first()?->count ?? 0;
                $data[] = $count;
            }
            $datasets[] = [
                'label' => $serviceName,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'labels' => $dates->map(fn (\DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $d): string => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
            'datasets' => array_slice($datasets, 0, 5), // Limit to top 5 services
        ];
    }

    #[Computed]
    public function summaryStats(): array
    {
        $totalAttendance = $this->serviceData->sum('total_attendance');
        $totalServices = $this->serviceData->count();
        $topService = $this->serviceData->first();

        return [
            'total_attendance' => $totalAttendance,
            'total_services' => $totalServices,
            'avg_per_service' => $totalServices > 0 ? round($totalAttendance / $totalServices, 0) : 0,
            'top_service' => $topService?->name ?? 'N/A',
            'top_service_count' => $topService?->total_attendance ?? 0,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Service Name', 'Service Type', 'Total Attendance', 'Unique Members', 'Unique Visitors', 'Service Days', 'Avg Per Service'];
        $filename = $this->generateFilename('service-attendance', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Service Name', 'Service Type', 'Total Attendance', 'Unique Members', 'Unique Visitors', 'Service Days', 'Avg Per Service'];
        $filename = $this->generateFilename('service-attendance', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return $this->serviceData->map(fn ($service): array => [
            $service->name,
            ucfirst($service->service_type),
            $service->total_attendance,
            $service->unique_members,
            $service->unique_visitors,
            $service->service_days,
            $service->avg_per_service,
        ]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.reports.attendance.service-wise-attendance');
    }
}
