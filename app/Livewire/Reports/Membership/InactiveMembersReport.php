<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Membership;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class InactiveMembersReport extends Component
{
    use HasReportExport, WithPagination;

    public Branch $branch;

    #[Url]
    public int $inactivityThreshold = 30;

    public string $sortBy = 'last_attendance';

    public string $sortDirection = 'asc';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function updatedInactivityThreshold(): void
    {
        $this->resetPage();
        unset($this->members, $this->totalInactiveMembers);
    }

    public function setThreshold(int $days): void
    {
        $this->inactivityThreshold = $days;
        $this->resetPage();
        unset($this->members, $this->totalInactiveMembers);
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
    public function members(): LengthAwarePaginator
    {
        $cutoffDate = now()->subDays($this->inactivityThreshold);

        // Get members with their last attendance date
        $membersQuery = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join) {
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
    public function totalInactiveMembers(): int
    {
        $cutoffDate = now()->subDays($this->inactivityThreshold);

        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join) {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.id, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL OR last_attendance < ?', [$cutoffDate])
            ->get()
            ->count();
    }

    #[Computed]
    public function totalActiveMembers(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->count();
    }

    #[Computed]
    public function inactivityRate(): float
    {
        $total = $this->totalActiveMembers;

        return $total > 0 ? round(($this->totalInactiveMembers / $total) * 100, 1) : 0;
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Last Attendance', 'Days Inactive', 'City'];
        $filename = $this->generateFilename('inactive-members', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Last Attendance', 'Days Inactive', 'City'];
        $filename = $this->generateFilename('inactive-members', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        $cutoffDate = now()->subDays($this->inactivityThreshold);

        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->leftJoin('attendance', function ($join) {
                $join->on('members.id', '=', 'attendance.member_id')
                    ->where('attendance.branch_id', '=', $this->branch->id);
            })
            ->selectRaw('members.*, MAX(attendance.date) as last_attendance')
            ->groupBy('members.id')
            ->havingRaw('last_attendance IS NULL OR last_attendance < ?', [$cutoffDate])
            ->orderByRaw('last_attendance IS NULL DESC, last_attendance ASC')
            ->get()
            ->map(function ($member) {
                $lastAttendance = $member->last_attendance ? \Carbon\Carbon::parse($member->last_attendance) : null;
                $daysInactive = $lastAttendance ? $lastAttendance->diffInDays(now()) : 'Never attended';

                return [
                    $member->fullName(),
                    $member->email ?? '',
                    $member->phone ?? '',
                    $lastAttendance?->format('Y-m-d') ?? 'Never',
                    $daysInactive,
                    $member->city ?? '',
                ];
            });
    }

    public function render()
    {
        return view('livewire.reports.membership.inactive-members-report');
    }
}
