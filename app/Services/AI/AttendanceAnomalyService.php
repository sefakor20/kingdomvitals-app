<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Tenant\Member;
use App\Services\AI\DTOs\AttendanceAnomaly;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceAnomalyService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Detect attendance anomalies for a member.
     */
    public function detectAnomaly(Member $member): ?AttendanceAnomaly
    {
        $config = config('ai.scoring.attendance');
        $baselineWeeks = $config['baseline_weeks'] ?? 8;
        $comparisonWeeks = $config['comparison_weeks'] ?? 4;
        $declineThreshold = $config['decline_threshold_percent'] ?? 50;

        // Get baseline attendance (older period)
        $baselineStart = now()->subWeeks($baselineWeeks + $comparisonWeeks);
        $baselineEnd = now()->subWeeks($comparisonWeeks);

        $baselineCount = $member->attendance()
            ->whereBetween('date', [$baselineStart, $baselineEnd])
            ->count();

        // Get recent attendance (comparison period)
        $recentCount = $member->attendance()
            ->where('date', '>=', now()->subWeeks($comparisonWeeks))
            ->count();

        // Calculate average per week
        $baselineAvg = $baselineCount / $baselineWeeks;
        $recentAvg = $recentCount / $comparisonWeeks;

        // Skip if baseline is too low to compare
        if ($baselineAvg < 0.5) {
            return null;
        }

        // Calculate percentage change
        $percentageChange = $baselineAvg > 0
            ? (($recentAvg - $baselineAvg) / $baselineAvg) * 100
            : 0;

        // Only flag if decline exceeds threshold
        if ($percentageChange >= -$declineThreshold) {
            return null;
        }

        $factors = $this->buildAnomalyFactors(
            $member,
            $baselineAvg,
            $recentAvg,
            $percentageChange
        );

        $lastAttendance = $member->attendance()->latest('date')->first();

        // Calculate anomaly score (0-100, higher = more concerning)
        $score = $this->calculateAnomalyScore($percentageChange, $baselineAvg, $recentCount);

        return new AttendanceAnomaly(
            memberId: $member->id,
            memberName: $member->fullName(),
            score: $score,
            baselineAttendance: round($baselineAvg, 2),
            recentAttendance: round($recentAvg, 2),
            percentageChange: round($percentageChange, 1),
            factors: $factors,
            lastAttendanceDate: $lastAttendance ? Carbon::parse($lastAttendance->date) : null,
        );
    }

    /**
     * Detect anomalies for all members in a branch.
     *
     * @return Collection<int, AttendanceAnomaly>
     */
    public function detectAnomaliesForBranch(string $branchId, int $limit = 50): Collection
    {
        $config = config('ai.scoring.attendance');
        $baselineWeeks = $config['baseline_weeks'] ?? 8;

        // Get members who have attended at least once in baseline period
        $members = Member::query()
            ->where('primary_branch_id', $branchId)
            ->whereHas('attendance', function ($query) use ($baselineWeeks) {
                $query->where('date', '>=', now()->subWeeks($baselineWeeks));
            })
            ->get();

        return $members
            ->map(fn (Member $member) => $this->detectAnomaly($member))
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Update member records with detected anomalies.
     */
    public function updateMemberAnomalyScores(string $branchId): int
    {
        $anomalies = $this->detectAnomaliesForBranch($branchId, 100);
        $updated = 0;

        foreach ($anomalies as $anomaly) {
            Member::where('id', $anomaly->memberId)->update([
                'attendance_anomaly_score' => $anomaly->score,
                'attendance_anomaly_detected_at' => now(),
            ]);
            $updated++;
        }

        // Clear old anomalies for members not in current list
        $anomalyIds = $anomalies->pluck('memberId')->toArray();

        Member::where('primary_branch_id', $branchId)
            ->whereNotNull('attendance_anomaly_detected_at')
            ->whereNotIn('id', $anomalyIds)
            ->update([
                'attendance_anomaly_score' => null,
                'attendance_anomaly_detected_at' => null,
            ]);

        return $updated;
    }

    /**
     * Get members with active attendance anomalies.
     *
     * @return Collection<int, Member>
     */
    public function getMembersWithAnomalies(string $branchId, int $limit = 20): Collection
    {
        return Member::where('primary_branch_id', $branchId)
            ->whereNotNull('attendance_anomaly_score')
            ->whereNotNull('attendance_anomaly_detected_at')
            ->where('attendance_anomaly_detected_at', '>=', now()->subDays(7))
            ->orderByDesc('attendance_anomaly_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate anomaly score based on severity.
     */
    protected function calculateAnomalyScore(
        float $percentageChange,
        float $baselineAvg,
        int $recentCount
    ): float {
        $score = 0;

        // Base score from percentage decline
        $declineScore = min(abs($percentageChange), 100);
        $score += $declineScore * 0.5;

        // Bonus for complete absence (no recent attendance at all)
        if ($recentCount === 0) {
            $score += 30;
        }

        // Weight by how active they were (more active = more concerning drop)
        if ($baselineAvg >= 3) {
            $score += 15; // Very active member
        } elseif ($baselineAvg >= 2) {
            $score += 10; // Active member
        } elseif ($baselineAvg >= 1) {
            $score += 5; // Regular member
        }

        return min(100, max(0, round($score, 2)));
    }

    /**
     * Build factors array for anomaly explanation.
     *
     * @return array<string, mixed>
     */
    protected function buildAnomalyFactors(
        Member $member,
        float $baselineAvg,
        float $recentAvg,
        float $percentageChange
    ): array {
        $factors = [];

        // Primary factor: attendance decline
        $factors['attendance_decline'] = [
            'description' => sprintf(
                'Attendance dropped from %.1f to %.1f times per week',
                $baselineAvg,
                $recentAvg
            ),
            'impact' => 'high',
            'value' => round($percentageChange, 1),
        ];

        // Check for complete absence
        if ($recentAvg === 0.0) {
            $lastAttendance = $member->attendance()->latest('date')->first();
            $daysSince = $lastAttendance
                ? Carbon::parse($lastAttendance->date)->diffInDays(now())
                : null;

            $factors['complete_absence'] = [
                'description' => 'No attendance in recent weeks',
                'impact' => 'critical',
                'days_since_last' => $daysSince,
            ];
        }

        // Check donation correlation
        $recentDonations = $member->donations()
            ->where('donation_date', '>=', now()->subWeeks(4))
            ->count();

        $previousDonations = $member->donations()
            ->whereBetween('donation_date', [now()->subWeeks(8), now()->subWeeks(4)])
            ->count();

        if ($previousDonations > 0 && $recentDonations === 0) {
            $factors['giving_stopped'] = [
                'description' => 'Giving has also stopped',
                'impact' => 'medium',
            ];
        }

        return $factors;
    }
}
