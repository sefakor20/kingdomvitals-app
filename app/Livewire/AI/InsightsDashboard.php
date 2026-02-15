<?php

declare(strict_types=1);

namespace App\Livewire\AI;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\ClusterHealthLevel;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\MembershipStatus;
use App\Enums\PrayerRequestStatus;
use App\Enums\PrayerUrgencyLevel;
use App\Enums\SmsEngagementLevel;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\AttendanceForecast;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\FinancialForecast;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\PrayerSummary;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class InsightsDashboard extends Component
{
    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('view', $branch);
        $this->branch = $branch;
    }

    // ============================================
    // AI ALERTS
    // ============================================

    #[Computed]
    public function recentAlerts(): Collection
    {
        return AiAlert::forBranch($this->branch->id)
            ->recent(7)
            ->orderBySeverity()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function unreadAlertsCount(): int
    {
        return AiAlert::forBranch($this->branch->id)
            ->unread()
            ->count();
    }

    #[Computed]
    public function highPriorityAlertsCount(): int
    {
        return AiAlert::forBranch($this->branch->id)
            ->highPriority()
            ->unacknowledged()
            ->count();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function alertStats(): array
    {
        $alerts = AiAlert::forBranch($this->branch->id)->recent(7)->get();

        $bySeverity = [];
        foreach (AlertSeverity::cases() as $severity) {
            $bySeverity[$severity->value] = $alerts->where('severity', $severity)->count();
        }

        $byType = [];
        foreach (AiAlertType::cases() as $type) {
            $count = $alerts->where('alert_type', $type)->count();
            if ($count > 0) {
                $byType[$type->value] = $count;
            }
        }

        return [
            'total' => $alerts->count(),
            'unread' => $alerts->where('is_read', false)->count(),
            'critical' => $bySeverity[AlertSeverity::Critical->value] ?? 0,
            'high' => $bySeverity[AlertSeverity::High->value] ?? 0,
            'by_severity' => $bySeverity,
            'by_type' => $byType,
        ];
    }

    public function markAlertAsRead(string $alertId): void
    {
        $alert = AiAlert::find($alertId);
        if ($alert && $alert->branch_id === $this->branch->id) {
            $alert->markAsRead();
        }
    }

    public function acknowledgeAlert(string $alertId): void
    {
        $alert = AiAlert::find($alertId);
        if ($alert && $alert->branch_id === $this->branch->id) {
            $alert->acknowledge(auth()->id());
        }
    }

    public function markRecommendationActedOn(string $alertId): void
    {
        $alert = AiAlert::find($alertId);
        if ($alert && $alert->branch_id === $this->branch->id) {
            $alert->markRecommendationActedOn();
        }
    }

    // ============================================
    // AT-RISK DONORS
    // ============================================

    #[Computed]
    public function atRiskDonors(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('churn_risk_score', '>', 70)
            ->orderByDesc('churn_risk_score')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function atRiskDonorsCount(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('churn_risk_score', '>', 70)
            ->count();
    }

    // ============================================
    // ATTENDANCE ANOMALIES
    // ============================================

    #[Computed]
    public function attendanceAnomalies(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->whereNotNull('attendance_anomaly_score')
            ->whereNotNull('attendance_anomaly_detected_at')
            ->where('attendance_anomaly_detected_at', '>=', now()->subDays(7))
            ->orderByDesc('attendance_anomaly_score')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function attendanceAnomaliesCount(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->whereNotNull('attendance_anomaly_detected_at')
            ->where('attendance_anomaly_detected_at', '>=', now()->subDays(7))
            ->count();
    }

    // ============================================
    // HIGH POTENTIAL VISITORS
    // ============================================

    #[Computed]
    public function highPotentialVisitors(): Collection
    {
        return Visitor::where('branch_id', $this->branch->id)
            ->where('is_converted', false)
            ->where('conversion_score', '>=', 70)
            ->orderByDesc('conversion_score')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function highPotentialVisitorsCount(): int
    {
        return Visitor::where('branch_id', $this->branch->id)
            ->where('is_converted', false)
            ->where('conversion_score', '>=', 70)
            ->count();
    }

    // ============================================
    // MEMBER LIFECYCLE
    // ============================================

    /**
     * Get member lifecycle stage distribution.
     *
     * @return array<string, array{count: int, stage: LifecycleStage, percentage: float}>
     */
    #[Computed]
    public function lifecycleDistribution(): array
    {
        $counts = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('lifecycle_stage')
            ->selectRaw('lifecycle_stage, COUNT(*) as count')
            ->groupBy('lifecycle_stage')
            ->pluck('count', 'lifecycle_stage')
            ->toArray();

        $total = array_sum($counts);

        $distribution = [];
        foreach (LifecycleStage::cases() as $stage) {
            $count = $counts[$stage->value] ?? 0;
            $distribution[$stage->value] = [
                'count' => $count,
                'stage' => $stage,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    #[Computed]
    public function membersNeedingAttention(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereIn('lifecycle_stage', [
                LifecycleStage::AtRisk,
                LifecycleStage::Disengaging,
                LifecycleStage::Dormant,
                LifecycleStage::Inactive,
            ])
            ->orderByRaw("FIELD(lifecycle_stage, 'at_risk', 'disengaging', 'dormant', 'inactive')")
            ->limit(15)
            ->get();
    }

    #[Computed]
    public function membersNeedingAttentionCount(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereIn('lifecycle_stage', [
                LifecycleStage::AtRisk,
                LifecycleStage::Disengaging,
                LifecycleStage::Dormant,
                LifecycleStage::Inactive,
            ])
            ->count();
    }

    // ============================================
    // CLUSTER HEALTH
    // ============================================

    /**
     * @return array<string, array{count: int, level: ClusterHealthLevel, percentage: float}>
     */
    #[Computed]
    public function clusterHealthDistribution(): array
    {
        $counts = Cluster::where('branch_id', $this->branch->id)
            ->whereNotNull('health_level')
            ->selectRaw('health_level, COUNT(*) as count')
            ->groupBy('health_level')
            ->pluck('count', 'health_level')
            ->toArray();

        $total = array_sum($counts);

        $distribution = [];
        foreach (ClusterHealthLevel::cases() as $level) {
            $count = $counts[$level->value] ?? 0;
            $distribution[$level->value] = [
                'count' => $count,
                'level' => $level,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    #[Computed]
    public function clustersNeedingAttention(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->whereIn('health_level', [
                ClusterHealthLevel::Struggling,
                ClusterHealthLevel::Critical,
            ])
            ->orderByRaw("FIELD(health_level, 'critical', 'struggling')")
            ->limit(5)
            ->get();
    }

    // ============================================
    // HOUSEHOLD ENGAGEMENT
    // ============================================

    /**
     * @return array<string, array{count: int, level: HouseholdEngagementLevel, percentage: float}>
     */
    #[Computed]
    public function householdEngagementDistribution(): array
    {
        $counts = Household::where('branch_id', $this->branch->id)
            ->whereNotNull('engagement_level')
            ->selectRaw('engagement_level, COUNT(*) as count')
            ->groupBy('engagement_level')
            ->pluck('count', 'engagement_level')
            ->toArray();

        $total = array_sum($counts);

        $distribution = [];
        foreach (HouseholdEngagementLevel::cases() as $level) {
            $count = $counts[$level->value] ?? 0;
            $distribution[$level->value] = [
                'count' => $count,
                'level' => $level,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    #[Computed]
    public function householdsNeedingOutreach(): Collection
    {
        return Household::where('branch_id', $this->branch->id)
            ->whereIn('engagement_level', [
                HouseholdEngagementLevel::Disengaged,
                HouseholdEngagementLevel::Low,
                HouseholdEngagementLevel::PartiallyEngaged,
            ])
            ->orderBy('engagement_score')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function householdsNeedingOutreachCount(): int
    {
        return Household::where('branch_id', $this->branch->id)
            ->whereIn('engagement_level', [
                HouseholdEngagementLevel::Disengaged,
                HouseholdEngagementLevel::Low,
                HouseholdEngagementLevel::PartiallyEngaged,
            ])
            ->count();
    }

    // ============================================
    // PRAYER REQUESTS
    // ============================================

    /**
     * @return array<string, array{count: int, level: PrayerUrgencyLevel}>
     */
    #[Computed]
    public function prayerUrgencyDistribution(): array
    {
        $counts = PrayerRequest::where('branch_id', $this->branch->id)
            ->where('status', PrayerRequestStatus::Open)
            ->whereNotNull('urgency_level')
            ->selectRaw('urgency_level, COUNT(*) as count')
            ->groupBy('urgency_level')
            ->pluck('count', 'urgency_level')
            ->toArray();

        $distribution = [];
        foreach (PrayerUrgencyLevel::cases() as $level) {
            $distribution[$level->value] = [
                'count' => $counts[$level->value] ?? 0,
                'level' => $level,
            ];
        }

        return $distribution;
    }

    #[Computed]
    public function criticalPrayerRequests(): Collection
    {
        return PrayerRequest::with('member')
            ->where('branch_id', $this->branch->id)
            ->where('status', PrayerRequestStatus::Open)
            ->whereIn('urgency_level', [
                PrayerUrgencyLevel::Critical,
                PrayerUrgencyLevel::High,
            ])
            ->orderByRaw("FIELD(urgency_level, 'critical', 'high')")
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function openPrayerRequestsCount(): int
    {
        return PrayerRequest::where('branch_id', $this->branch->id)
            ->where('status', PrayerRequestStatus::Open)
            ->count();
    }

    #[Computed]
    public function latestPrayerSummary(): ?PrayerSummary
    {
        return PrayerSummary::where('branch_id', $this->branch->id)
            ->orderByDesc('period_end')
            ->first();
    }

    #[Computed]
    public function latestWeeklyPrayerSummary(): ?PrayerSummary
    {
        return PrayerSummary::where('branch_id', $this->branch->id)
            ->weekly()
            ->orderByDesc('period_end')
            ->first();
    }

    #[Computed]
    public function latestMonthlyPrayerSummary(): ?PrayerSummary
    {
        return PrayerSummary::where('branch_id', $this->branch->id)
            ->monthly()
            ->orderByDesc('period_end')
            ->first();
    }

    // ============================================
    // SMS ENGAGEMENT
    // ============================================

    /**
     * @return array<string, array{count: int, level: SmsEngagementLevel, percentage: float}>
     */
    #[Computed]
    public function smsEngagementDistribution(): array
    {
        $counts = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('sms_engagement_level')
            ->selectRaw('sms_engagement_level, COUNT(*) as count')
            ->groupBy('sms_engagement_level')
            ->pluck('count', 'sms_engagement_level')
            ->toArray();

        $total = array_sum($counts);

        $distribution = [];
        foreach (SmsEngagementLevel::cases() as $level) {
            $count = $counts[$level->value] ?? 0;
            $distribution[$level->value] = [
                'count' => $count,
                'level' => $level,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    #[Computed]
    public function lowSmsEngagementCount(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereIn('sms_engagement_level', [
                SmsEngagementLevel::Low,
                SmsEngagementLevel::Inactive,
            ])
            ->count();
    }

    // ============================================
    // ATTENDANCE FORECAST
    // ============================================

    #[Computed]
    public function attendanceForecasts(): Collection
    {
        return AttendanceForecast::with('service')
            ->whereHas('service', function ($query): void {
                $query->where('branch_id', $this->branch->id);
            })
            ->where('forecast_date', '>=', now())
            ->where('forecast_date', '<=', now()->addWeeks(4))
            ->orderBy('forecast_date')
            ->get();
    }

    // ============================================
    // FINANCIAL FORECAST
    // ============================================

    #[Computed]
    public function financialForecasts(): Collection
    {
        return FinancialForecast::where('branch_id', $this->branch->id)
            ->monthly()
            ->upcoming()
            ->limit(4)
            ->get();
    }

    #[Computed]
    public function financialForecastSummary(): array
    {
        $forecasts = $this->financialForecasts;

        if ($forecasts->isEmpty()) {
            return [
                'total_predicted' => 0,
                'total_budget' => 0,
                'total_gap' => 0,
                'periods_on_track' => 0,
                'periods_at_risk' => 0,
                'average_confidence' => 0,
            ];
        }

        $totalPredicted = $forecasts->sum('predicted_total');
        $totalBudget = $forecasts->sum('budget_target');
        $totalGap = $totalPredicted - $totalBudget;

        $periodsOnTrack = $forecasts->filter(fn ($f): bool => $f->isOnTrack() === true)->count();
        $periodsAtRisk = $forecasts->filter(fn ($f): bool => $f->isOnTrack() === false)->count();

        $avgConfidence = $forecasts->avg('confidence_score');

        return [
            'total_predicted' => $totalPredicted,
            'total_budget' => $totalBudget,
            'total_gap' => $totalGap,
            'periods_on_track' => $periodsOnTrack,
            'periods_at_risk' => $periodsAtRisk,
            'average_confidence' => round($avgConfidence ?? 0, 1),
        ];
    }

    #[Computed]
    public function quarterlyFinancialForecast(): ?FinancialForecast
    {
        return FinancialForecast::where('branch_id', $this->branch->id)
            ->quarterly()
            ->upcoming()
            ->first();
    }

    // ============================================
    // GIVING TRENDS
    // ============================================

    #[Computed]
    public function majorDonors(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('donor_tier', 'top_10')
            ->whereNotNull('giving_analyzed_at')
            ->orderByDesc('giving_consistency_score')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function decliningDonors(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'declining')
            ->whereNotNull('giving_analyzed_at')
            ->orderBy('giving_growth_rate')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function growingDonors(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'growing')
            ->whereNotNull('giving_analyzed_at')
            ->orderByDesc('giving_growth_rate')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function newDonors(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'new')
            ->whereNotNull('giving_analyzed_at')
            ->orderByDesc('giving_analyzed_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return array<string, array{count: int, percentage: float}>
     */
    #[Computed]
    public function donorTierDistribution(): array
    {
        $counts = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('donor_tier')
            ->selectRaw('donor_tier, COUNT(*) as count')
            ->groupBy('donor_tier')
            ->pluck('count', 'donor_tier')
            ->toArray();

        $total = array_sum($counts);

        $tiers = ['top_10', 'top_25', 'middle', 'bottom'];
        $distribution = [];

        foreach ($tiers as $tier) {
            $count = $counts[$tier] ?? 0;
            $distribution[$tier] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function givingTrendCounts(): array
    {
        $counts = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('giving_trend')
            ->selectRaw('giving_trend, COUNT(*) as count')
            ->groupBy('giving_trend')
            ->pluck('count', 'giving_trend')
            ->toArray();

        return [
            'growing' => $counts['growing'] ?? 0,
            'stable' => $counts['stable'] ?? 0,
            'declining' => $counts['declining'] ?? 0,
            'new' => $counts['new'] ?? 0,
            'lapsed' => $counts['lapsed'] ?? 0,
        ];
    }

    // ============================================
    // SUMMARY STATS
    // ============================================

    #[Computed]
    public function summaryStats(): array
    {
        return [
            'total_members' => Member::where('primary_branch_id', $this->branch->id)
                ->where('status', MembershipStatus::Active)
                ->count(),
            'total_clusters' => Cluster::where('branch_id', $this->branch->id)->count(),
            'total_households' => Household::where('branch_id', $this->branch->id)->count(),
            'total_visitors' => Visitor::where('branch_id', $this->branch->id)
                ->where('is_converted', false)
                ->count(),
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.ai.insights-dashboard');
    }
}
