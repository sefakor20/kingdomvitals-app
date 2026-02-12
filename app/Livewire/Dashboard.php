<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ClusterHealthLevel;
use App\Enums\FollowUpOutcome;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\MembershipStatus;
use App\Enums\PlanModule;
use App\Enums\PrayerRequestStatus;
use App\Enums\PrayerUrgencyLevel;
use App\Enums\SmsEngagementLevel;
use App\Livewire\Concerns\HasQuotaComputed;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Services\BranchContextService;
use App\Services\PlanAccessService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    use HasQuotaComputed;

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

        // Clear quota caches
        unset($this->memberQuota);
        unset($this->smsQuota);
        unset($this->storageQuota);
        unset($this->branchQuota);
        unset($this->hasAnyQuotaLimits);
        unset($this->planName);

        // Clear AI insights caches
        unset($this->aiInsightsEnabled);
        unset($this->atRiskDonorsCount);
        unset($this->attendanceAnomaliesCount);
        unset($this->highPotentialVisitors);
        unset($this->lifecycleDistribution);
        unset($this->membersNeedingAttentionCount);
        unset($this->clusterHealthDistribution);
        unset($this->clustersNeedingAttention);
        unset($this->householdEngagementDistribution);
        unset($this->householdsNeedingOutreachCount);
        unset($this->urgentPrayerRequestsCount);
        unset($this->criticalPrayerRequests);
        unset($this->openPrayerRequestsCount);
        unset($this->smsEngagementDistribution);
        unset($this->lowSmsEngagementCount);
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

    // ============================================
    // PLAN QUOTA METRICS
    // ============================================
    // memberQuota, smsQuota, storageQuota, branchQuota are provided by HasQuotaComputed trait

    /**
     * Check if the tenant has any quota limits (not all unlimited).
     */
    #[Computed]
    public function hasAnyQuotaLimits(): bool
    {
        return ! $this->memberQuota['unlimited']
            || ! $this->smsQuota['unlimited']
            || ! $this->storageQuota['unlimited']
            || ! $this->branchQuota['unlimited'];
    }

    /**
     * Get the current plan name for display.
     */
    #[Computed]
    public function planName(): ?string
    {
        $plan = app(PlanAccessService::class)->getPlan();

        return $plan?->name;
    }

    // ============================================
    // AI INSIGHTS METRICS
    // ============================================

    /**
     * Check if AI insights module is enabled.
     */
    #[Computed]
    public function aiInsightsEnabled(): bool
    {
        return app(PlanAccessService::class)->hasModule(PlanModule::AiInsights);
    }

    /**
     * Get count of donors at high churn risk.
     */
    #[Computed]
    public function atRiskDonorsCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->where('churn_risk_score', '>', 70)
            ->count();
    }

    /**
     * Get count of members with attendance anomalies.
     */
    #[Computed]
    public function attendanceAnomaliesCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->whereNotNull('attendance_anomaly_detected_at')
            ->where('attendance_anomaly_detected_at', '>=', now()->subDays(7))
            ->count();
    }

    /**
     * Get visitors with high conversion scores.
     */
    #[Computed]
    public function highPotentialVisitors(): Collection
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return collect();
        }

        return Visitor::where('branch_id', $this->currentBranchId)
            ->where('is_converted', false)
            ->where('conversion_score', '>=', 70)
            ->orderByDesc('conversion_score')
            ->limit(5)
            ->get();
    }

    /**
     * Get member lifecycle stage distribution.
     *
     * @return array<string, array{count: int, stage: LifecycleStage}>
     */
    #[Computed]
    public function lifecycleDistribution(): array
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return [];
        }

        $counts = Member::where('primary_branch_id', $this->currentBranchId)
            ->whereNotNull('lifecycle_stage')
            ->selectRaw('lifecycle_stage, COUNT(*) as count')
            ->groupBy('lifecycle_stage')
            ->pluck('count', 'lifecycle_stage')
            ->toArray();

        $distribution = [];
        foreach (LifecycleStage::cases() as $stage) {
            $distribution[$stage->value] = [
                'count' => $counts[$stage->value] ?? 0,
                'stage' => $stage,
            ];
        }

        return $distribution;
    }

    /**
     * Get count of members needing attention (at-risk, disengaging, dormant, inactive).
     */
    #[Computed]
    public function membersNeedingAttentionCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->whereIn('lifecycle_stage', [
                LifecycleStage::AtRisk,
                LifecycleStage::Disengaging,
                LifecycleStage::Dormant,
                LifecycleStage::Inactive,
            ])
            ->count();
    }

    /**
     * Get cluster health level distribution.
     *
     * @return array<string, array{count: int, level: ClusterHealthLevel}>
     */
    #[Computed]
    public function clusterHealthDistribution(): array
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return [];
        }

        $counts = Cluster::where('branch_id', $this->currentBranchId)
            ->whereNotNull('health_level')
            ->selectRaw('health_level, COUNT(*) as count')
            ->groupBy('health_level')
            ->pluck('count', 'health_level')
            ->toArray();

        $distribution = [];
        foreach (ClusterHealthLevel::cases() as $level) {
            $distribution[$level->value] = [
                'count' => $counts[$level->value] ?? 0,
                'level' => $level,
            ];
        }

        return $distribution;
    }

    /**
     * Get clusters needing attention (struggling or critical health).
     */
    #[Computed]
    public function clustersNeedingAttention(): Collection
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return collect();
        }

        return Cluster::where('branch_id', $this->currentBranchId)
            ->whereIn('health_level', [
                ClusterHealthLevel::Struggling,
                ClusterHealthLevel::Critical,
            ])
            ->orderByRaw("FIELD(health_level, 'critical', 'struggling')")
            ->limit(5)
            ->get();
    }

    /**
     * Get household engagement level distribution.
     *
     * @return array<string, array{count: int, level: HouseholdEngagementLevel}>
     */
    #[Computed]
    public function householdEngagementDistribution(): array
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return [];
        }

        $counts = Household::where('branch_id', $this->currentBranchId)
            ->whereNotNull('engagement_level')
            ->selectRaw('engagement_level, COUNT(*) as count')
            ->groupBy('engagement_level')
            ->pluck('count', 'engagement_level')
            ->toArray();

        $distribution = [];
        foreach (HouseholdEngagementLevel::cases() as $level) {
            $distribution[$level->value] = [
                'count' => $counts[$level->value] ?? 0,
                'level' => $level,
            ];
        }

        return $distribution;
    }

    /**
     * Get count of households needing outreach (low, disengaged, or partially engaged).
     */
    #[Computed]
    public function householdsNeedingOutreachCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return Household::where('branch_id', $this->currentBranchId)
            ->whereIn('engagement_level', [
                HouseholdEngagementLevel::Low,
                HouseholdEngagementLevel::Disengaged,
                HouseholdEngagementLevel::PartiallyEngaged,
            ])
            ->count();
    }

    /**
     * Get count of urgent prayer requests (elevated, high, or critical).
     */
    #[Computed]
    public function urgentPrayerRequestsCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return PrayerRequest::where('branch_id', $this->currentBranchId)
            ->where('status', PrayerRequestStatus::Open)
            ->whereIn('urgency_level', [
                PrayerUrgencyLevel::Elevated,
                PrayerUrgencyLevel::High,
                PrayerUrgencyLevel::Critical,
            ])
            ->count();
    }

    /**
     * Get critical prayer requests requiring immediate attention.
     */
    #[Computed]
    public function criticalPrayerRequests(): Collection
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return collect();
        }

        return PrayerRequest::with('member')
            ->where('branch_id', $this->currentBranchId)
            ->where('status', PrayerRequestStatus::Open)
            ->where('urgency_level', PrayerUrgencyLevel::Critical)
            ->latest()
            ->limit(3)
            ->get();
    }

    /**
     * Get count of open prayer requests.
     */
    #[Computed]
    public function openPrayerRequestsCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return PrayerRequest::where('branch_id', $this->currentBranchId)
            ->where('status', PrayerRequestStatus::Open)
            ->count();
    }

    /**
     * Get SMS engagement level distribution.
     *
     * @return array<string, array{count: int, level: SmsEngagementLevel}>
     */
    #[Computed]
    public function smsEngagementDistribution(): array
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return [];
        }

        $counts = Member::where('primary_branch_id', $this->currentBranchId)
            ->whereNotNull('sms_engagement_level')
            ->selectRaw('sms_engagement_level, COUNT(*) as count')
            ->groupBy('sms_engagement_level')
            ->pluck('count', 'sms_engagement_level')
            ->toArray();

        $distribution = [];
        foreach (SmsEngagementLevel::cases() as $level) {
            $distribution[$level->value] = [
                'count' => $counts[$level->value] ?? 0,
                'level' => $level,
            ];
        }

        return $distribution;
    }

    /**
     * Get count of members with low SMS engagement.
     */
    #[Computed]
    public function lowSmsEngagementCount(): int
    {
        if (! $this->aiInsightsEnabled || ! $this->currentBranchId) {
            return 0;
        }

        return Member::where('primary_branch_id', $this->currentBranchId)
            ->whereIn('sms_engagement_level', [
                SmsEngagementLevel::Low,
                SmsEngagementLevel::Inactive,
            ])
            ->count();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.dashboard');
    }
}
