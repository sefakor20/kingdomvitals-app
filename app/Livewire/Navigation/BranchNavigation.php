<?php

namespace App\Livewire\Navigation;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\Service;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use App\Services\BranchContextService;
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
    public function canViewMembers(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Member::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewClusters(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Cluster::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewServices(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Service::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewVisitors(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Visitor::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewAttendance(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Attendance::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewDonations(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Donation::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewExpenses(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Expense::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewPledges(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [Pledge::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewFinanceReports(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewReports', [Donation::class, $this->currentBranch]);
    }

    #[Computed]
    public function canViewSms(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('viewAny', [SmsLog::class, $this->currentBranch]);
    }

    #[Computed]
    public function canUpdateBranch(): bool
    {
        return $this->currentBranch &&
            auth()->user()?->can('update', $this->currentBranch);
    }

    public function render()
    {
        return view('livewire.navigation.branch-navigation');
    }
}
