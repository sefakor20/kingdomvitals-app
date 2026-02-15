<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\ClusterHealthLevel;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\AiAlertSetting;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AiAlertService
{
    public function __construct(
        protected ?AlertRecommendationService $recommendationService = null,
    ) {
        $this->recommendationService ??= new AlertRecommendationService;
    }

    /**
     * Run all alert checks for a branch.
     *
     * @return Collection<int, AiAlert>
     */
    public function processAllAlerts(Branch $branch): Collection
    {
        $alerts = collect();

        $alerts = $alerts->merge($this->checkChurnRiskAlerts($branch));
        $alerts = $alerts->merge($this->checkAttendanceAnomalyAlerts($branch));
        $alerts = $alerts->merge($this->checkLifecycleTransitionAlerts($branch));
        $alerts = $alerts->merge($this->checkCriticalPrayerAlerts($branch));
        $alerts = $alerts->merge($this->checkClusterHealthAlerts($branch));

        return $alerts->merge($this->checkHouseholdDisengagementAlerts($branch));
    }

    /**
     * Check for members with high churn risk scores.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkChurnRiskAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::ChurnRisk
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        $threshold = $setting->getEffectiveThreshold() ?? 70;

        // Find members with high churn risk
        $atRiskMembers = Member::where('primary_branch_id', $branch->id)
            ->where('churn_risk_score', '>=', $threshold)
            ->whereNotNull('churn_risk_score')
            ->get();

        $alerts = collect();

        foreach ($atRiskMembers as $member) {
            if ($this->shouldCreateAlert($branch, AiAlertType::ChurnRisk, $member)) {
                $churnScore = (float) $member->churn_risk_score;
                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::ChurnRisk,
                    alertable: $member,
                    title: "High churn risk detected for {$member->fullName()}",
                    description: "Member {$member->fullName()} has a churn risk score of {$churnScore}%, exceeding the threshold of {$threshold}%.",
                    severity: $this->determineSeverity($churnScore, (float) $threshold),
                    data: [
                        'churn_score' => $member->churn_risk_score,
                        'threshold' => $threshold,
                        'factors' => $member->churn_risk_factors ?? [],
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        if ($alerts->isNotEmpty()) {
            $setting->markTriggered();
        }

        return $alerts;
    }

    /**
     * Check for members with attendance anomalies.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkAttendanceAnomalyAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::AttendanceAnomaly
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        $threshold = $setting->getEffectiveThreshold() ?? 50;

        // Find members with significant attendance drops
        $anomalyMembers = Member::where('primary_branch_id', $branch->id)
            ->whereNotNull('attendance_anomaly_score')
            ->where('attendance_anomaly_score', '>=', $threshold)
            ->get();

        $alerts = collect();

        foreach ($anomalyMembers as $member) {
            if ($this->shouldCreateAlert($branch, AiAlertType::AttendanceAnomaly, $member)) {
                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::AttendanceAnomaly,
                    alertable: $member,
                    title: "Attendance anomaly detected for {$member->fullName()}",
                    description: "Member {$member->fullName()} shows a significant attendance pattern change with an anomaly score of {$member->attendance_anomaly_score}.",
                    severity: AlertSeverity::Medium,
                    data: [
                        'anomaly_score' => $member->attendance_anomaly_score,
                        'threshold' => $threshold,
                        'anomaly_factors' => $member->attendance_anomaly_factors ?? [],
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        if ($alerts->isNotEmpty()) {
            $setting->markTriggered();
        }

        return $alerts;
    }

    /**
     * Check for members who transitioned to at-risk lifecycle stages.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkLifecycleTransitionAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::LifecycleChange
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        // Find members in at-risk stages
        $atRiskStages = [
            LifecycleStage::AtRisk,
            LifecycleStage::Dormant,
            LifecycleStage::Disengaging,
        ];

        $atRiskMembers = Member::where('primary_branch_id', $branch->id)
            ->whereIn('lifecycle_stage', $atRiskStages)
            ->whereNotNull('lifecycle_stage_changed_at')
            ->where('lifecycle_stage_changed_at', '>=', now()->subHours($setting->cooldown_hours ?: 24))
            ->get();

        $alerts = collect();

        foreach ($atRiskMembers as $member) {
            if ($this->shouldCreateAlert($branch, AiAlertType::LifecycleChange, $member)) {
                $stage = $member->lifecycle_stage;
                $severity = $stage === LifecycleStage::AtRisk ? AlertSeverity::High : AlertSeverity::Medium;

                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::LifecycleChange,
                    alertable: $member,
                    title: "Lifecycle transition: {$member->fullName()} is now {$stage->label()}",
                    description: "Member {$member->fullName()} has transitioned to the {$stage->label()} stage. {$stage->description()}",
                    severity: $severity,
                    data: [
                        'current_stage' => $stage->value,
                        'previous_stage' => $member->previous_lifecycle_stage?->value,
                        'changed_at' => $member->lifecycle_stage_changed_at?->toIso8601String(),
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        if ($alerts->isNotEmpty()) {
            $setting->markTriggered();
        }

        return $alerts;
    }

    /**
     * Check for critical or high-urgency prayer requests.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkCriticalPrayerAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::CriticalPrayer
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        // Find critical/high urgency prayer requests that haven't been alerted
        $criticalPrayers = PrayerRequest::where('branch_id', $branch->id)
            ->whereIn('urgency_level', [
                PrayerUrgencyLevel::Critical,
                PrayerUrgencyLevel::High,
            ])
            ->where('status', '!=', 'answered')
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        $alerts = collect();

        foreach ($criticalPrayers as $prayer) {
            if ($this->shouldCreateAlert($branch, AiAlertType::CriticalPrayer, $prayer)) {
                $urgency = $prayer->urgency_level;
                $severity = $urgency === PrayerUrgencyLevel::Critical
                    ? AlertSeverity::Critical
                    : AlertSeverity::High;

                $memberName = $prayer->member?->fullName() ?? 'Anonymous';

                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::CriticalPrayer,
                    alertable: $prayer,
                    title: "{$urgency->label()} prayer request requires attention",
                    description: "A {$urgency->label()} prayer request from {$memberName} needs pastoral attention: {$prayer->title}",
                    severity: $severity,
                    data: [
                        'prayer_id' => $prayer->id,
                        'urgency_level' => $urgency->value,
                        'category' => $prayer->category?->value,
                        'member_id' => $prayer->member_id,
                        'submitted_at' => $prayer->submitted_at?->toIso8601String(),
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        // No cooldown marking for critical prayers - always process immediately

        return $alerts;
    }

    /**
     * Check for clusters with declining health.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkClusterHealthAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::ClusterHealth
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        $threshold = $setting->getEffectiveThreshold() ?? 50;

        // Find clusters in struggling or critical health
        $unhealthyClusters = Cluster::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereIn('health_level', [
                ClusterHealthLevel::Struggling->value,
                ClusterHealthLevel::Critical->value,
            ])
            ->where('health_score', '<', $threshold)
            ->get();

        $alerts = collect();

        foreach ($unhealthyClusters as $cluster) {
            if ($this->shouldCreateAlert($branch, AiAlertType::ClusterHealth, $cluster)) {
                // health_level may already be cast to enum or be a string
                $healthLevel = $cluster->health_level instanceof ClusterHealthLevel
                    ? $cluster->health_level
                    : ClusterHealthLevel::tryFrom($cluster->health_level);

                $severity = $healthLevel === ClusterHealthLevel::Critical
                    ? AlertSeverity::Critical
                    : AlertSeverity::High;

                $healthLevelValue = $healthLevel?->value ?? 'unknown';
                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::ClusterHealth,
                    alertable: $cluster,
                    title: "Cluster '{$cluster->name}' health is {$healthLevelValue}",
                    description: "Cluster {$cluster->name} has a health score of {$cluster->health_score}/100 and is in {$healthLevelValue} condition.",
                    severity: $severity,
                    data: [
                        'cluster_id' => $cluster->id,
                        'health_score' => $cluster->health_score,
                        'health_level' => $healthLevelValue,
                        'member_count' => $cluster->members()->count(),
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        if ($alerts->isNotEmpty()) {
            $setting->markTriggered();
        }

        return $alerts;
    }

    /**
     * Check for households with disengaged status.
     *
     * @return Collection<int, AiAlert>
     */
    public function checkHouseholdDisengagementAlerts(Branch $branch): Collection
    {
        $setting = AiAlertSetting::getOrCreateForBranch(
            $branch->id,
            AiAlertType::HouseholdDisengagement
        );

        if (! $setting->canTrigger()) {
            return collect();
        }

        // Find disengaged households
        $disengagedHouseholds = Household::where('branch_id', $branch->id)
            ->where('engagement_level', HouseholdEngagementLevel::Disengaged->value)
            ->get();

        $alerts = collect();

        foreach ($disengagedHouseholds as $household) {
            if ($this->shouldCreateAlert($branch, AiAlertType::HouseholdDisengagement, $household)) {
                $memberCount = $household->members()->count();

                $alert = $this->createAlert(
                    branch: $branch,
                    type: AiAlertType::HouseholdDisengagement,
                    alertable: $household,
                    title: "Household '{$household->name}' is disengaged",
                    description: "The {$household->name} household ({$memberCount} members) has dropped to disengaged status with an engagement score of {$household->engagement_score}.",
                    severity: AlertSeverity::Medium,
                    data: [
                        'household_id' => $household->id,
                        'engagement_score' => $household->engagement_score,
                        'engagement_level' => $household->engagement_level,
                        'member_count' => $memberCount,
                    ]
                );

                if ($alert instanceof \App\Models\Tenant\AiAlert) {
                    $alerts->push($alert);
                }
            }
        }

        if ($alerts->isNotEmpty()) {
            $setting->markTriggered();
        }

        return $alerts;
    }

    /**
     * Create an alert record.
     */
    public function createAlert(
        Branch $branch,
        AiAlertType $type,
        Model $alertable,
        string $title,
        string $description,
        AlertSeverity $severity,
        array $data = []
    ): ?AiAlert {
        $alert = AiAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'alertable_type' => $alertable->getMorphClass(),
            'alertable_id' => $alertable->getKey(),
            'data' => $data,
        ]);

        // Generate and attach recommendations
        if ($alert && config('ai.recommendations.enabled', true)) {
            $recommendations = $this->recommendationService->getRecommendationsForAlert($alert);
            if ($recommendations !== []) {
                $alert->update([
                    'recommendations' => $this->recommendationService->toStorableFormat($recommendations),
                ]);
            }
        }

        return $alert;
    }

    /**
     * Check if an alert should be created (cooldown check).
     */
    protected function shouldCreateAlert(
        Branch $branch,
        AiAlertType $type,
        Model $alertable,
        int $cooldownHours = 24
    ): bool {
        $setting = AiAlertSetting::getOrCreateForBranch($branch->id, $type);
        $effectiveCooldown = $setting->cooldown_hours ?: $cooldownHours;

        return ! AiAlert::existsForEntity(
            branchId: $branch->id,
            alertType: $type,
            alertableType: $alertable->getMorphClass(),
            alertableId: (string) $alertable->getKey(),
            cooldownHours: $effectiveCooldown
        );
    }

    /**
     * Determine severity based on score exceeding threshold.
     */
    protected function determineSeverity(float $score, float $threshold): AlertSeverity
    {
        $excess = $score - $threshold;

        return match (true) {
            $excess >= 25 => AlertSeverity::Critical,
            $excess >= 15 => AlertSeverity::High,
            $excess >= 5 => AlertSeverity::Medium,
            default => AlertSeverity::Low,
        };
    }

    /**
     * Get recent alerts for a branch.
     *
     * @return Collection<int, AiAlert>
     */
    public function getRecentAlerts(string $branchId, int $days = 7, int $limit = 50): Collection
    {
        return AiAlert::forBranch($branchId)
            ->recent($days)
            ->orderBySeverity()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread alerts count for a branch.
     */
    public function getUnreadCount(string $branchId): int
    {
        return AiAlert::forBranch($branchId)
            ->unread()
            ->count();
    }

    /**
     * Get high priority unacknowledged alerts for a branch.
     *
     * @return Collection<int, AiAlert>
     */
    public function getHighPriorityAlerts(string $branchId, int $limit = 10): Collection
    {
        return AiAlert::forBranch($branchId)
            ->highPriority()
            ->unacknowledged()
            ->orderBySeverity()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get alerts grouped by type for a branch.
     *
     * @return Collection<string, Collection<int, AiAlert>>
     */
    public function getAlertsByType(string $branchId, int $days = 7): Collection
    {
        return AiAlert::forBranch($branchId)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn (AiAlert $alert) => $alert->alert_type->value);
    }

    /**
     * Get alert statistics for a branch.
     *
     * @return array<string, mixed>
     */
    public function getAlertStats(string $branchId, int $days = 30): array
    {
        $alerts = AiAlert::forBranch($branchId)->recent($days)->get();

        $bySeverity = [];
        foreach (AlertSeverity::cases() as $severity) {
            $bySeverity[$severity->value] = $alerts->where('severity', $severity)->count();
        }

        $byType = [];
        foreach (AiAlertType::cases() as $type) {
            $byType[$type->value] = $alerts->where('alert_type', $type)->count();
        }

        return [
            'total' => $alerts->count(),
            'unread' => $alerts->where('is_read', false)->count(),
            'unacknowledged' => $alerts->where('is_acknowledged', false)->count(),
            'critical' => $alerts->where('severity', AlertSeverity::Critical)->count(),
            'by_severity' => $bySeverity,
            'by_type' => $byType,
            'period_days' => $days,
        ];
    }

    /**
     * Mark multiple alerts as read.
     */
    public function markAlertsAsRead(array $alertIds): int
    {
        return AiAlert::whereIn('id', $alertIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(string $alertId, string $userId): bool
    {
        $alert = AiAlert::find($alertId);

        if (! $alert) {
            return false;
        }

        return $alert->acknowledge($userId);
    }
}
