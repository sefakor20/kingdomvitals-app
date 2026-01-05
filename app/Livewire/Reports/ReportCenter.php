<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReportCenter extends Component
{
    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    #[Computed]
    public function membershipStats(): array
    {
        $totalMembers = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->count();

        $newMembersThisMonth = Member::where('primary_branch_id', $this->branch->id)
            ->whereMonth('joined_at', now()->month)
            ->whereYear('joined_at', now()->year)
            ->count();

        return [
            'total' => $totalMembers,
            'new_this_month' => $newMembersThisMonth,
        ];
    }

    #[Computed]
    public function attendanceStats(): array
    {
        $lastSundayAttendance = Attendance::where('branch_id', $this->branch->id)
            ->whereDate('date', now()->previous('Sunday'))
            ->count();

        $thisMonthAttendance = Attendance::where('branch_id', $this->branch->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        return [
            'last_sunday' => $lastSundayAttendance,
            'this_month' => $thisMonthAttendance,
        ];
    }

    #[Computed]
    public function financialStats(): array
    {
        $thisMonthDonations = Donation::where('branch_id', $this->branch->id)
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');

        $ytdDonations = Donation::where('branch_id', $this->branch->id)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');

        return [
            'this_month' => $thisMonthDonations,
            'ytd' => $ytdDonations,
        ];
    }

    #[Computed]
    public function visitorStats(): array
    {
        $thisMonthVisitors = Visitor::where('branch_id', $this->branch->id)
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $convertedThisMonth = Visitor::where('branch_id', $this->branch->id)
            ->where('is_converted', true)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return [
            'this_month' => $thisMonthVisitors,
            'converted' => $convertedThisMonth,
        ];
    }

    public function render()
    {
        return view('livewire.reports.report-center');
    }
}
