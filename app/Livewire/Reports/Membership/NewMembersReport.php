<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Membership;

use App\Livewire\Concerns\HasReportExport;
use App\Livewire\Concerns\HasReportFilters;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class NewMembersReport extends Component
{
    use HasReportExport, HasReportFilters, WithPagination;

    public Branch $branch;

    public string $sortBy = 'joined_at';

    public string $sortDirection = 'desc';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
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

    protected function clearReportCaches(): void
    {
        unset($this->members, $this->totalNewMembers, $this->chartData);
        $this->resetPage();
    }

    #[Computed]
    public function members(): LengthAwarePaginator
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereBetween('joined_at', [$this->startDate, $this->endDate])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);
    }

    #[Computed]
    public function totalNewMembers(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->whereBetween('joined_at', [$this->startDate, $this->endDate])
            ->count();
    }

    #[Computed]
    public function chartData(): array
    {
        $members = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereBetween('joined_at', [$this->startDate, $this->endDate])
            ->selectRaw('DATE(joined_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        $current = $this->startDate->copy();
        while ($current <= $this->endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            $data[] = $members->firstWhere('date', $dateStr)?->count ?? 0;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function comparisonStats(): array
    {
        $currentCount = $this->totalNewMembers;

        // Calculate previous period for comparison
        $daysDiff = $this->startDate->diffInDays($this->endDate);
        $previousStart = $this->startDate->copy()->subDays($daysDiff + 1);
        $previousEnd = $this->startDate->copy()->subDay();

        $previousCount = Member::where('primary_branch_id', $this->branch->id)
            ->whereBetween('joined_at', [$previousStart, $previousEnd])
            ->count();

        $change = $previousCount > 0
            ? round((($currentCount - $previousCount) / $previousCount) * 100, 1)
            : ($currentCount > 0 ? 100 : 0);

        return [
            'current' => $currentCount,
            'previous' => $previousCount,
            'change' => $change,
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Gender', 'Status', 'Joined Date', 'City'];
        $filename = $this->generateFilename('new-members', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Gender', 'Status', 'Joined Date', 'City'];
        $filename = $this->generateFilename('new-members', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->whereBetween('joined_at', [$this->startDate, $this->endDate])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get()
            ->map(fn (Member $member): array => [
                $member->fullName(),
                $member->email ?? '',
                $member->phone ?? '',
                $member->gender?->value ? ucfirst($member->gender->value) : '',
                ucfirst($member->status->value),
                $member->joined_at?->format('Y-m-d') ?? '',
                $member->city ?? '',
            ]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.reports.membership.new-members-report');
    }
}
