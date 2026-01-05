<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Attendance;

use App\Livewire\Concerns\HasReportExport;
use App\Livewire\Concerns\HasReportFilters;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class FirstTimeVisitorsReport extends Component
{
    use HasReportExport, HasReportFilters, WithPagination;

    public Branch $branch;

    #[Url]
    public string $status = '';

    #[Url]
    public string $source = '';

    public string $sortBy = 'visit_date';

    public string $sortDirection = 'desc';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
    }

    protected function clearReportCaches(): void
    {
        unset($this->visitors, $this->summaryStats, $this->sourceBreakdown, $this->chartData);
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function visitors(): LengthAwarePaginator
    {
        return Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->source, fn ($q) => $q->where('how_did_you_hear', $this->source))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);
    }

    #[Computed]
    public function summaryStats(): array
    {
        $total = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->count();

        $converted = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->where('is_converted', true)
            ->count();

        $followedUp = Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->where('follow_up_count', '>', 0)
            ->count();

        return [
            'total' => $total,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
            'followed_up' => $followedUp,
            'follow_up_rate' => $total > 0 ? round(($followedUp / $total) * 100, 1) : 0,
        ];
    }

    #[Computed]
    public function sourceBreakdown(): Collection
    {
        return Visitor::where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->whereNotNull('how_did_you_hear')
            ->selectRaw('how_did_you_hear, COUNT(*) as count')
            ->groupBy('how_did_you_hear')
            ->orderByDesc('count')
            ->get();
    }

    #[Computed]
    public function sources(): Collection
    {
        return Visitor::where('branch_id', $this->branch->id)
            ->whereNotNull('how_did_you_hear')
            ->distinct()
            ->pluck('how_did_you_hear');
    }

    #[Computed]
    public function statuses(): array
    {
        return \App\Enums\VisitorStatus::cases();
    }

    #[Computed]
    public function chartData(): array
    {
        $visitors = Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->selectRaw('DATE(visit_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        $current = $this->startDate->copy();
        while ($current <= $this->endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            $data[] = $visitors->firstWhere('date', $dateStr)?->count ?? 0;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['status', 'source']);
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->status !== '' || $this->source !== '';
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Visit Date', 'Source', 'Status', 'Converted', 'Follow-ups'];
        $filename = $this->generateFilename('visitors', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Visit Date', 'Source', 'Status', 'Converted', 'Follow-ups'];
        $filename = $this->generateFilename('visitors', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereBetween('visit_date', [$this->startDate, $this->endDate])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->source, fn ($q) => $q->where('how_did_you_hear', $this->source))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get()
            ->map(fn (Visitor $visitor) => [
                $visitor->fullName(),
                $visitor->email ?? '',
                $visitor->phone ?? '',
                $visitor->visit_date?->format('Y-m-d') ?? '',
                $visitor->how_did_you_hear ?? '',
                $visitor->status?->value ? ucfirst($visitor->status->value) : '',
                $visitor->is_converted ? 'Yes' : 'No',
                $visitor->follow_up_count ?? 0,
            ]);
    }

    public function render()
    {
        return view('livewire.reports.attendance.first-time-visitors-report');
    }
}
