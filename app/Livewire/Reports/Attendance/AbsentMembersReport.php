<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Attendance;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class AbsentMembersReport extends Component
{
    use HasReportExport, WithPagination;

    public Branch $branch;

    #[Url]
    public int $weeks = 2;

    public string $sortBy = 'last_attendance';

    public string $sortDirection = 'asc';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function setWeeks(int $weeks): void
    {
        $this->weeks = $weeks;
        $this->resetPage();
        unset($this->absentMembers, $this->summaryStats);
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
    public function absentMembers(): LengthAwarePaginator
    {
        $cutoffDate = now()->subWeeks($this->weeks);

        $membersQuery = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join): void {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.*, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL OR last_attendance < ?', [$cutoffDate]);

        if ($this->sortBy === 'last_attendance') {
            $membersQuery->orderByRaw("last_attendance IS NULL DESC, last_attendance {$this->sortDirection}");
        } else {
            $membersQuery->orderBy($this->sortBy, $this->sortDirection);
        }

        return $membersQuery->paginate(25);
    }

    #[Computed]
    public function summaryStats(): array
    {
        $cutoffDate = now()->subWeeks($this->weeks);

        $absentCount = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join): void {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.id, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL OR last_attendance < ?', [$cutoffDate])
            ->get()
            ->count();

        $totalActive = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->count();

        $neverAttended = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join): void {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.id, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL')
            ->get()
            ->count();

        return [
            'absent_count' => $absentCount,
            'total_active' => $totalActive,
            'absence_rate' => $totalActive > 0 ? round(($absentCount / $totalActive) * 100, 1) : 0,
            'never_attended' => $neverAttended,
        ];
    }

    #[Computed]
    public function absenceBreakdown(): array
    {
        // Break down by weeks absent
        $cutoffs = [
            '2-4 weeks' => [now()->subWeeks(4), now()->subWeeks(2)],
            '4-8 weeks' => [now()->subWeeks(8), now()->subWeeks(4)],
            '8+ weeks' => [now()->subYears(10), now()->subWeeks(8)],
        ];

        $breakdown = [];
        foreach ($cutoffs as $label => [$start, $end]) {
            $count = Member::query()
                ->where('primary_branch_id', $this->branch->id)
                ->where('status', 'active')
                ->leftJoin('attendance', function ($join): void {
                    $join->on('members.id', '=', 'attendance.member_id')
                        ->where('attendance.branch_id', '=', $this->branch->id);
                })
                ->selectRaw('members.id, MAX(attendance.date) as last_attendance')
                ->groupBy('members.id')
                ->havingBetween('last_attendance', [$start, $end])
                ->get()
                ->count();

            $breakdown[$label] = $count;
        }

        return $breakdown;
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Last Attendance', 'Weeks Absent', 'City'];
        $filename = $this->generateFilename('absent-members', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Last Attendance', 'Weeks Absent', 'City'];
        $filename = $this->generateFilename('absent-members', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        $cutoffDate = now()->subWeeks($this->weeks);

        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join): void {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.*, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL OR last_attendance < ?', [$cutoffDate])
            ->orderByRaw('last_attendance IS NULL DESC, last_attendance ASC')
            ->get()
            ->map(function ($member): array {
                $lastAttendance = $member->last_attendance ? Carbon::parse($member->last_attendance) : null;
                $weeksAbsent = $lastAttendance instanceof \Carbon\Carbon ? $lastAttendance->diffInWeeks(now()) : 'Never';

                return [
                    $member->fullName(),
                    $member->email ?? '',
                    $member->phone ?? '',
                    $lastAttendance?->format('Y-m-d') ?? 'Never',
                    $weeksAbsent,
                    $member->city ?? '',
                ];
            });
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.reports.attendance.absent-members-report');
    }
}
