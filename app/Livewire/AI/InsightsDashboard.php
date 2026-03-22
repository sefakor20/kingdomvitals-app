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
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class InsightsDashboard extends Component
{
    public Branch $branch;

    // ============================================
    // FILTER PROPERTIES
    // ============================================

    #[Url]
    public string $dateRange = '7';

    #[Url]
    public string $alertTypeFilter = '';

    #[Url]
    public string $alertSeverityFilter = '';

    #[Url]
    public string $alertStatusFilter = '';

    public function mount(Branch $branch): void
    {
        $this->authorize('view', $branch);
        $this->branch = $branch;
    }

    /**
     * Reset all filters to defaults.
     */
    public function resetFilters(): void
    {
        $this->dateRange = '7';
        $this->alertTypeFilter = '';
        $this->alertSeverityFilter = '';
        $this->alertStatusFilter = '';
    }

    /**
     * Get the number of days from the date range filter.
     */
    protected function getDateRangeDays(): int
    {
        return match ($this->dateRange) {
            '7' => 7,
            '30' => 30,
            '90' => 90,
            default => 7,
        };
    }

    // ============================================
    // AI ALERTS
    // ============================================

    #[Computed]
    public function recentAlerts(): Collection
    {
        $query = AiAlert::forBranch($this->branch->id)
            ->recent($this->getDateRangeDays())
            ->orderBySeverity()
            ->orderBy('created_at', 'desc');

        // Apply alert type filter
        if ($this->alertTypeFilter !== '') {
            $alertType = AiAlertType::tryFrom($this->alertTypeFilter);
            if ($alertType) {
                $query->where('alert_type', $alertType);
            }
        }

        // Apply severity filter
        if ($this->alertSeverityFilter !== '') {
            $severity = AlertSeverity::tryFrom($this->alertSeverityFilter);
            if ($severity) {
                $query->where('severity', $severity);
            }
        }

        // Apply status filter
        if ($this->alertStatusFilter !== '') {
            match ($this->alertStatusFilter) {
                'unread' => $query->unread(),
                'read' => $query->where('is_read', true),
                'acknowledged' => $query->where('is_acknowledged', true),
                'unacknowledged' => $query->unacknowledged(),
                default => null,
            };
        }

        return $query->limit(20)->get();
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
        $alerts = AiAlert::forBranch($this->branch->id)->recent($this->getDateRangeDays())->get();

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

    /**
     * Get available alert types for filter dropdown.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availableAlertTypes(): array
    {
        $types = [];
        foreach (AiAlertType::cases() as $type) {
            $types[$type->value] = $type->label();
        }

        return $types;
    }

    /**
     * Get available alert severities for filter dropdown.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availableAlertSeverities(): array
    {
        $severities = [];
        foreach (AlertSeverity::cases() as $severity) {
            $severities[$severity->value] = $severity->label();
        }

        return $severities;
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

    // ============================================
    // EXPORT METHODS
    // ============================================

    /**
     * Export alerts to CSV.
     */
    public function exportAlertsCsv(): StreamedResponse
    {
        $alerts = AiAlert::forBranch($this->branch->id)
            ->recent($this->getDateRangeDays())
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'ai-alerts-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($alerts): void {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'Title',
                'Description',
                'Type',
                'Severity',
                'Is Read',
                'Is Acknowledged',
                'Created At',
                'Related Entity',
            ]);

            // CSV Data
            foreach ($alerts as $alert) {
                fputcsv($handle, [
                    $alert->title,
                    $alert->description,
                    $alert->alert_type->label(),
                    $alert->severity->label(),
                    $alert->is_read ? 'Yes' : 'No',
                    $alert->is_acknowledged ? 'Yes' : 'No',
                    $alert->created_at->format('Y-m-d H:i:s'),
                    $alert->relatedEntityName ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export financial forecasts to CSV.
     */
    public function exportForecastsCsv(): StreamedResponse
    {
        $forecasts = FinancialForecast::where('branch_id', $this->branch->id)
            ->orderBy('period_start', 'desc')
            ->limit(24)
            ->get();

        $filename = 'financial-forecasts-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($forecasts): void {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'Period',
                'Period Type',
                'Predicted Total',
                'Predicted Tithes',
                'Predicted Offerings',
                'Predicted Special',
                'Predicted Other',
                'Budget Target',
                'Confidence Score',
                'On Track',
                'Period Start',
                'Period End',
            ]);

            // CSV Data
            foreach ($forecasts as $forecast) {
                fputcsv($handle, [
                    $forecast->period_label,
                    $forecast->period_type,
                    $forecast->predicted_total,
                    $forecast->predicted_tithes ?? 0,
                    $forecast->predicted_offerings ?? 0,
                    $forecast->predicted_special ?? 0,
                    $forecast->predicted_other ?? 0,
                    $forecast->budget_target ?? '',
                    $forecast->confidence_score,
                    $forecast->isOnTrack() === true ? 'Yes' : ($forecast->isOnTrack() === false ? 'No' : 'N/A'),
                    $forecast->period_start->format('Y-m-d'),
                    $forecast->period_end->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export attendance forecasts to CSV.
     */
    public function exportAttendanceForecastsCsv(): StreamedResponse
    {
        $forecasts = AttendanceForecast::with('service')
            ->whereHas('service', function ($query): void {
                $query->where('branch_id', $this->branch->id);
            })
            ->orderBy('forecast_date', 'desc')
            ->limit(50)
            ->get();

        $filename = 'attendance-forecasts-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($forecasts): void {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'Forecast Date',
                'Service',
                'Predicted Attendance',
                'Predicted Members',
                'Predicted Visitors',
                'Confidence Score',
                'Actual Attendance',
            ]);

            // CSV Data
            foreach ($forecasts as $forecast) {
                fputcsv($handle, [
                    $forecast->forecast_date->format('Y-m-d'),
                    $forecast->service?->name ?? 'Unknown',
                    $forecast->predicted_attendance,
                    $forecast->predicted_members ?? '',
                    $forecast->predicted_visitors ?? '',
                    $forecast->confidence_score ?? '',
                    $forecast->actual_attendance ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ============================================
    // TREND VISUALIZATION DATA
    // ============================================

    /**
     * Get alert trend data for the last 12 weeks.
     *
     * @return array<int, array{week: string, critical: int, high: int, medium: int, low: int}>
     */
    #[Computed]
    public function alertTrendData(): array
    {
        $weeks = [];
        $startDate = now()->subWeeks(12)->startOfWeek();

        // Initialize weeks
        for ($i = 0; $i < 12; $i++) {
            $weekStart = $startDate->copy()->addWeeks($i);
            $weeks[$weekStart->format('Y-W')] = [
                'week' => $weekStart->format('M d'),
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];
        }

        // Get alerts for the period
        $alerts = AiAlert::forBranch($this->branch->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        // Group by week
        foreach ($alerts as $alert) {
            $weekKey = $alert->created_at->format('Y-W');
            if (isset($weeks[$weekKey])) {
                $severity = $alert->severity->value;
                if (isset($weeks[$weekKey][$severity])) {
                    $weeks[$weekKey][$severity]++;
                }
            }
        }

        return array_values($weeks);
    }

    /**
     * Get lifecycle stage trend data for the last 6 months.
     * Uses member lifecycle_stage_updated_at for historical tracking.
     *
     * @return array<int, array{month: string, stages: array<string, int>}>
     */
    #[Computed]
    public function lifecycleTrendData(): array
    {
        $months = [];
        $startDate = now()->subMonths(5)->startOfMonth();

        // Get current distribution for the current month
        $currentDistribution = $this->lifecycleDistribution;

        // For now, show current distribution (historical tracking would require lifecycle_stage_updated_at)
        for ($i = 0; $i < 6; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $months[] = [
                'month' => $monthStart->format('M Y'),
                'stages' => collect($currentDistribution)->mapWithKeys(fn ($data, $key) => [$key => $data['count']])->toArray(),
            ];
        }

        return $months;
    }

    /**
     * Get forecast accuracy data comparing predictions vs actuals.
     *
     * @return array{attendance: array, financial: array}
     */
    #[Computed]
    public function forecastAccuracyData(): array
    {
        // Attendance forecast accuracy
        $attendanceForecasts = AttendanceForecast::with('service')
            ->whereHas('service', function ($query): void {
                $query->where('branch_id', $this->branch->id);
            })
            ->whereNotNull('actual_attendance')
            ->where('forecast_date', '>=', now()->subMonths(3))
            ->orderBy('forecast_date', 'desc')
            ->limit(20)
            ->get();

        $attendanceAccuracy = [];
        $totalAttendanceMape = 0;
        $attendanceCount = 0;

        foreach ($attendanceForecasts as $forecast) {
            if ($forecast->actual_attendance > 0) {
                $error = abs($forecast->predicted_attendance - $forecast->actual_attendance);
                $mape = ($error / $forecast->actual_attendance) * 100;
                $totalAttendanceMape += $mape;
                $attendanceCount++;

                $attendanceAccuracy[] = [
                    'date' => $forecast->forecast_date->format('M d'),
                    'predicted' => $forecast->predicted_attendance,
                    'actual' => $forecast->actual_attendance,
                    'accuracy' => round(100 - $mape, 1),
                ];
            }
        }

        // Financial forecast accuracy
        $financialForecasts = FinancialForecast::where('branch_id', $this->branch->id)
            ->whereNotNull('actual_total')
            ->where('period_start', '>=', now()->subMonths(6))
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        $financialAccuracy = [];
        $totalFinancialMape = 0;
        $financialCount = 0;

        foreach ($financialForecasts as $forecast) {
            if ($forecast->actual_total > 0) {
                $error = abs($forecast->predicted_total - $forecast->actual_total);
                $mape = ($error / $forecast->actual_total) * 100;
                $totalFinancialMape += $mape;
                $financialCount++;

                $financialAccuracy[] = [
                    'period' => $forecast->period_label,
                    'predicted' => $forecast->predicted_total,
                    'actual' => $forecast->actual_total,
                    'accuracy' => round(100 - $mape, 1),
                ];
            }
        }

        return [
            'attendance' => [
                'data' => array_reverse($attendanceAccuracy),
                'avg_mape' => $attendanceCount > 0 ? round($totalAttendanceMape / $attendanceCount, 1) : null,
                'accuracy_rate' => $attendanceCount > 0 ? round(100 - ($totalAttendanceMape / $attendanceCount), 1) : null,
                'sample_size' => $attendanceCount,
            ],
            'financial' => [
                'data' => array_reverse($financialAccuracy),
                'avg_mape' => $financialCount > 0 ? round($totalFinancialMape / $financialCount, 1) : null,
                'accuracy_rate' => $financialCount > 0 ? round(100 - ($totalFinancialMape / $financialCount), 1) : null,
                'sample_size' => $financialCount,
            ],
        ];
    }

    public function render(): Factory|View
    {
        return view('livewire.ai.insights-dashboard');
    }
}
