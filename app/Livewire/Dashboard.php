<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FollowUpOutcome;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Services\BranchContextService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public ?string $currentBranchId = null;

    public function mount(BranchContextService $branchContext): void
    {
        $this->currentBranchId = $branchContext->getCurrentBranchId()
            ?? $branchContext->getDefaultBranchId();

        if ($this->currentBranchId) {
            $branchContext->setCurrentBranch($this->currentBranchId);
        }
    }

    #[On('branch-switched')]
    public function handleBranchSwitch(string $branchId): void
    {
        $this->currentBranchId = $branchId;

        // Clear all computed property caches
        unset($this->currentBranch);
        unset($this->totalActiveMembers);
        unset($this->newMembersThisMonth);
        unset($this->newVisitorsThisMonth);
        unset($this->totalVisitors);
        unset($this->conversionRate);
        unset($this->overdueFollowUps);
        unset($this->pendingFollowUps);
        unset($this->donationsThisMonth);
        unset($this->lastServiceAttendance);
        unset($this->recentActivity);
    }

    #[Computed]
    public function currentBranch(): ?Branch
    {
        if (! $this->currentBranchId) {
            return null;
        }

        return Branch::find($this->currentBranchId);
    }

    // ============================================
    // MEMBER METRICS
    // ============================================

    #[Computed]
    public function totalActiveMembers(): int
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->where('status', MembershipStatus::Active)
            ->count();
    }

    #[Computed]
    public function newMembersThisMonth(): int
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->whereMonth('joined_at', now()->month)
            ->whereYear('joined_at', now()->year)
            ->count();
    }

    // ============================================
    // VISITOR METRICS
    // ============================================

    #[Computed]
    public function newVisitorsThisMonth(): int
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return Visitor::where('branch_id', $this->currentBranchId)
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();
    }

    #[Computed]
    public function totalVisitors(): int
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return Visitor::where('branch_id', $this->currentBranchId)->count();
    }

    #[Computed]
    public function conversionRate(): float
    {
        $total = $this->totalVisitors;

        if ($total === 0) {
            return 0;
        }

        $converted = Visitor::where('branch_id', $this->currentBranchId)
            ->where('is_converted', true)
            ->count();

        return round(($converted / $total) * 100, 1);
    }

    // ============================================
    // FOLLOW-UP METRICS
    // ============================================

    #[Computed]
    public function overdueFollowUps(): int
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return VisitorFollowUp::whereHas('visitor', function ($query): void {
            $query->where('branch_id', $this->currentBranchId);
        })
            ->where('is_scheduled', true)
            ->where('scheduled_at', '<', now())
            ->where('outcome', FollowUpOutcome::Pending)
            ->count();
    }

    #[Computed]
    public function pendingFollowUps(): Collection
    {
        if (! $this->currentBranchId) {
            return collect();
        }

        return VisitorFollowUp::with(['visitor', 'performedBy'])
            ->whereHas('visitor', function ($query): void {
                $query->where('branch_id', $this->currentBranchId);
            })
            ->where('is_scheduled', true)
            ->where('outcome', FollowUpOutcome::Pending)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();
    }

    // ============================================
    // FINANCIAL METRICS
    // ============================================

    #[Computed]
    public function donationsThisMonth(): float
    {
        if (! $this->currentBranchId) {
            return 0;
        }

        return (float) Donation::where('branch_id', $this->currentBranchId)
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');
    }

    // ============================================
    // ATTENDANCE METRICS
    // ============================================

    #[Computed]
    public function lastServiceAttendance(): ?array
    {
        if (! $this->currentBranchId) {
            return null;
        }

        $lastDate = Attendance::where('branch_id', $this->currentBranchId)
            ->max('date');

        if (! $lastDate) {
            return null;
        }

        $attendanceQuery = Attendance::where('branch_id', $this->currentBranchId)
            ->where('date', $lastDate);

        return [
            'date' => $lastDate,
            'total' => $attendanceQuery->count(),
            'members' => (clone $attendanceQuery)->whereNotNull('member_id')->count(),
            'visitors' => (clone $attendanceQuery)->whereNotNull('visitor_id')->count(),
        ];
    }

    // ============================================
    // ACTIVITY FEED
    // ============================================

    #[Computed]
    public function recentActivity(): Collection
    {
        if (! $this->currentBranchId) {
            return collect();
        }

        return MemberActivity::with(['member', 'user'])
            ->whereHas('member', function ($query): void {
                $query->where('primary_branch_id', $this->currentBranchId);
            })
            ->latest()
            ->limit(10)
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.dashboard');
    }
}
