<?php

namespace App\Livewire\Navigation;

use App\Enums\PlanModule;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\Service;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Services\BranchContextService;
use App\Services\PlanAccessService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BranchNavigation extends Component
{
    public ?string $currentBranchId = null;

    public function mount(BranchContextService $branchContext): void
    {
        $this->currentBranchId = $branchContext->getCurrentBranchId();
    }

    #[On('branch-switched')]
    public function onBranchSwitched(string $branchId): void
    {
        $this->currentBranchId = $branchId;
    }

    #[Computed]
    public function currentBranch(): ?Branch
    {
        if (! $this->currentBranchId) {
            return null;
        }

        return Branch::find($this->currentBranchId);
    }

    #[Computed]
    public function planAccess(): PlanAccessService
    {
        return app(PlanAccessService::class);
    }

    #[Computed]
    public function canViewMembers(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Members) &&
            auth()->user()?->can('viewAny', [Member::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewClusters(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Clusters) &&
            auth()->user()?->can('viewAny', [Cluster::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewServices(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Services) &&
            auth()->user()?->can('viewAny', [Service::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewDutyRosters(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::DutyRoster) &&
            auth()->user()?->can('viewAny', [DutyRoster::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewVisitors(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Visitors) &&
            auth()->user()?->can('viewAny', [Visitor::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewFollowUpQueue(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Visitors) &&
            auth()->user()?->can('viewAny', [VisitorFollowUp::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewAttendance(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Attendance) &&
            auth()->user()?->can('viewAny', [Attendance::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewDonations(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Donations) &&
            auth()->user()?->can('viewAny', [Donation::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewExpenses(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Expenses) &&
            auth()->user()?->can('viewAny', [Expense::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewPledges(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Pledges) &&
            auth()->user()?->can('viewAny', [Pledge::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewBudgets(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Budgets) &&
            auth()->user()?->can('viewAny', [Budget::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewFinanceReports(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Reports) &&
            auth()->user()?->can('viewReports', [Donation::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewSms(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Sms) &&
            auth()->user()?->can('viewAny', [SmsLog::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewEquipment(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Equipment) &&
            auth()->user()?->can('viewAny', [Equipment::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewHouseholds(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Households) &&
            auth()->user()?->can('viewAny', [Household::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewPrayerRequests(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::PrayerRequests) &&
            auth()->user()?->can('viewAny', [PrayerRequest::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewChildren(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Children) &&
            auth()->user()?->can('viewAny', [Member::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewGivingHistory(): bool
    {
        // All authenticated users can view their own giving history if donations module is enabled
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Donations) &&
            auth()->check();
    }

    #[Computed]
    public function canViewReports(): bool
    {
        return $this->currentBranch &&
            $this->planAccess->hasModule(PlanModule::Reports) &&
            auth()->user()?->can('viewReports', $this->currentBranch);
    }

    #[Computed]
    public function canUpdateBranch(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('update', $this->currentBranch);
    }

    #[Computed]
    public function canViewUsers(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [UserBranchAccess::class, $this->currentBranch]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.navigation.branch-navigation');
    }
}
