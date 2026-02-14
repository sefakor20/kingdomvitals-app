<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\LifecycleStage;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\LifecycleStageAssessment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MemberLifecycleService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Detect the lifecycle stage for a member.
     */
    public function detectStage(Member $member): LifecycleStageAssessment
    {
        return $this->detectHeuristicStage($member);
    }

    /**
     * Detect lifecycle stage using heuristic algorithm.
     */
    protected function detectHeuristicStage(Member $member): LifecycleStageAssessment
    {
        $config = config('ai.scoring.lifecycle', []);
        $factors = [];
        $previousStage = $member->lifecycle_stage;

        // Priority 1: Check inactive status
        if ($member->status === MembershipStatus::Inactive) {
            $factors['membership_status'] = [
                'value' => 'inactive',
                'description' => 'Member has inactive status',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Inactive,
                $previousStage,
                95.0,
                $factors
            );
        }

        // Priority 2: Check if still a visitor (prospect)
        $isVisitor = $this->isVisitorNotConverted($member);
        if ($isVisitor) {
            $factors['visitor_status'] = [
                'value' => true,
                'description' => 'Visitor has not converted to member',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Prospect,
                $previousStage,
                90.0,
                $factors
            );
        }

        // Get attendance and giving data
        $attendanceData = $this->getAttendanceData($member);
        $givingData = $this->getGivingData($member);

        // Priority 3: Check for new member (joined within 90 days)
        $newMemberDays = $config['new_member_days'] ?? 90;
        $daysSinceJoined = $member->joined_at
            ? Carbon::parse($member->joined_at)->diffInDays(now())
            : 999;

        if ($daysSinceJoined <= $newMemberDays) {
            $factors['new_member'] = [
                'value' => $daysSinceJoined,
                'description' => "Joined {$daysSinceJoined} days ago",
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::NewMember,
                $previousStage,
                85.0,
                $factors
            );
        }

        // Priority 4: Check for dormant (no attendance in 90+ days)
        $dormantDays = $config['dormant_days'] ?? 90;
        $daysSinceLastAttendance = $attendanceData['days_since_last'] ?? 999;

        if ($daysSinceLastAttendance >= $dormantDays) {
            $factors['dormant'] = [
                'value' => $daysSinceLastAttendance,
                'description' => "No attendance in {$daysSinceLastAttendance} days",
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Dormant,
                $previousStage,
                90.0,
                $factors
            );
        }

        // Priority 5: Check for At-Risk (high churn risk or attendance anomaly)
        $churnRiskThreshold = $config['churn_risk_at_risk_threshold'] ?? 70;
        $churnRisk = $member->churn_risk_score ?? 0;
        $hasAttendanceAnomaly = $member->attendance_anomaly_score >= 70;

        if ($churnRisk >= $churnRiskThreshold || $hasAttendanceAnomaly) {
            $factors['at_risk'] = [
                'churn_risk' => $churnRisk,
                'anomaly_score' => $member->attendance_anomaly_score ?? 0,
                'description' => $churnRisk >= $churnRiskThreshold
                    ? "High churn risk score: {$churnRisk}"
                    : 'Attendance anomaly detected',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::AtRisk,
                $previousStage,
                80.0,
                $factors
            );
        }

        // Priority 6: Check for Disengaging (moderate churn risk or declining attendance)
        $disengagingThreshold = $config['churn_risk_disengaging_threshold'] ?? 50;
        $isAttendanceDecline = $attendanceData['trend'] < -30;

        if ($churnRisk >= $disengagingThreshold || $isAttendanceDecline) {
            $factors['disengaging'] = [
                'churn_risk' => $churnRisk,
                'attendance_trend' => $attendanceData['trend'],
                'description' => $churnRisk >= $disengagingThreshold
                    ? "Moderate churn risk: {$churnRisk}"
                    : 'Declining attendance pattern',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Disengaging,
                $previousStage,
                75.0,
                $factors
            );
        }

        // Priority 7: Check for Engaged (6+ months regular attendance + active giving)
        $minAttendanceForEngaged = $config['min_attendance_for_engaged'] ?? 4;
        $minGivingForEngaged = $config['min_giving_for_engaged'] ?? 1;
        $monthsSinceJoined = $daysSinceJoined / 30;

        if (
            $monthsSinceJoined >= 6 &&
            $attendanceData['recent_count'] >= $minAttendanceForEngaged &&
            $givingData['recent_count'] >= $minGivingForEngaged
        ) {
            $factors['engaged'] = [
                'months_since_joined' => round($monthsSinceJoined, 1),
                'recent_attendance' => $attendanceData['recent_count'],
                'recent_giving' => $givingData['recent_count'],
                'description' => 'Regular attendance and active giving for 6+ months',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Engaged,
                $previousStage,
                85.0,
                $factors
            );
        }

        // Priority 8: Check for Growing (3-6 months regular attendance)
        if (
            $monthsSinceJoined >= 3 &&
            $attendanceData['recent_count'] >= 3
        ) {
            $factors['growing'] = [
                'months_since_joined' => round($monthsSinceJoined, 1),
                'recent_attendance' => $attendanceData['recent_count'],
                'description' => 'Regular attendance for 3+ months, developing engagement',
            ];

            return $this->buildAssessment(
                $member,
                LifecycleStage::Growing,
                $previousStage,
                70.0,
                $factors
            );
        }

        // Default: Growing (default for members not fitting other categories)
        $factors['default'] = [
            'description' => 'Default stage for active members still developing engagement',
        ];

        return $this->buildAssessment(
            $member,
            LifecycleStage::Growing,
            $previousStage,
            60.0,
            $factors
        );
    }

    /**
     * Build a lifecycle stage assessment.
     */
    protected function buildAssessment(
        Member $member,
        LifecycleStage $stage,
        ?LifecycleStage $previousStage,
        float $confidence,
        array $factors
    ): LifecycleStageAssessment {
        return new LifecycleStageAssessment(
            memberId: (string) $member->id,
            stage: $stage,
            previousStage: $previousStage,
            confidenceScore: $confidence,
            factors: $factors,
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Check if member is a visitor who hasn't converted.
     */
    protected function isVisitorNotConverted(Member $member): bool
    {
        // Members in the members table are already converted members.
        // Prospects/visitors are stored in the visitors table separately.
        // This method returns false since all members are converted by definition.
        return false;
    }

    /**
     * Get attendance data for a member.
     *
     * @return array{recent_count: int, days_since_last: int, trend: float}
     */
    protected function getAttendanceData(Member $member): array
    {
        // Get attendance in last 90 days
        $recentAttendance = $member->attendance()
            ->where('date', '>=', now()->subDays(90))
            ->count();

        // Get last attendance date
        $lastAttendance = $member->attendance()
            ->latest('date')
            ->first();

        $daysSinceLastAttendance = $lastAttendance
            ? Carbon::parse($lastAttendance->date)->diffInDays(now())
            : 999;

        // Calculate attendance trend (compare last 45 days to previous 45 days)
        $trend = $this->calculateAttendanceTrend($member);

        return [
            'recent_count' => $recentAttendance,
            'days_since_last' => (int) $daysSinceLastAttendance,
            'trend' => $trend,
        ];
    }

    /**
     * Calculate attendance trend percentage.
     */
    protected function calculateAttendanceTrend(Member $member): float
    {
        $recentCount = $member->attendance()
            ->where('date', '>=', now()->subDays(45))
            ->count();

        $previousCount = $member->attendance()
            ->whereBetween('date', [now()->subDays(90), now()->subDays(45)])
            ->count();

        if ($previousCount === 0) {
            return $recentCount > 0 ? 100 : 0;
        }

        return (($recentCount - $previousCount) / $previousCount) * 100;
    }

    /**
     * Get giving data for a member.
     *
     * @return array{recent_count: int, days_since_last: int, total_recent: float}
     */
    protected function getGivingData(Member $member): array
    {
        // Get donations in last 90 days
        $recentDonations = $member->donations()
            ->where('donation_date', '>=', now()->subDays(90))
            ->get();

        // Get last donation date
        $lastDonation = $member->donations()
            ->latest('donation_date')
            ->first();

        $daysSinceLastDonation = $lastDonation
            ? Carbon::parse($lastDonation->donation_date)->diffInDays(now())
            : 999;

        return [
            'recent_count' => $recentDonations->count(),
            'days_since_last' => (int) $daysSinceLastDonation,
            'total_recent' => (float) $recentDonations->sum('amount'),
        ];
    }

    /**
     * Get members by lifecycle stage.
     */
    public function getMembersByStage(string $branchId, LifecycleStage $stage, int $limit = 50): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $branchId)
            ->where('lifecycle_stage', $stage->value)
            ->orderByDesc('lifecycle_stage_changed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get members needing attention (at-risk, disengaging, dormant).
     */
    public function getMembersNeedingAttention(string $branchId, int $limit = 30): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $branchId)
            ->whereIn('lifecycle_stage', [
                LifecycleStage::AtRisk->value,
                LifecycleStage::Disengaging->value,
                LifecycleStage::Dormant->value,
            ])
            ->orderByRaw('FIELD(lifecycle_stage, ?, ?, ?)', [
                LifecycleStage::AtRisk->value,
                LifecycleStage::Disengaging->value,
                LifecycleStage::Dormant->value,
            ])
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent transitions.
     */
    public function getRecentTransitions(string $branchId, int $days = 7, int $limit = 20): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $branchId)
            ->whereNotNull('lifecycle_stage_changed_at')
            ->where('lifecycle_stage_changed_at', '>=', now()->subDays($days))
            ->orderByDesc('lifecycle_stage_changed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get lifecycle stage distribution for a branch.
     *
     * @return array<string, int>
     */
    public function getStageDistribution(string $branchId): array
    {
        $distribution = [];

        foreach (LifecycleStage::cases() as $stage) {
            $distribution[$stage->value] = Member::query()
                ->where('primary_branch_id', $branchId)
                ->where('lifecycle_stage', $stage->value)
                ->count();
        }

        return $distribution;
    }
}
