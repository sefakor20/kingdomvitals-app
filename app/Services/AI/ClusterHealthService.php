<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\ClusterHealthLevel;
use App\Enums\LifecycleStage;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\ClusterHealthAssessment;
use Illuminate\Support\Collection;

class ClusterHealthService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Calculate health assessment for a cluster.
     */
    public function calculateHealth(Cluster $cluster): ClusterHealthAssessment
    {
        return $this->calculateHeuristicHealth($cluster);
    }

    /**
     * Calculate health using heuristic algorithm.
     */
    protected function calculateHeuristicHealth(Cluster $cluster): ClusterHealthAssessment
    {
        $config = config('ai.scoring.cluster', []);
        $factors = [];
        $recommendations = [];
        $trends = [];

        // Get cluster members
        $members = $cluster->members()->get();

        if ($members->isEmpty()) {
            return $this->buildEmptyClusterAssessment($cluster);
        }

        // Calculate component scores
        $attendanceWeight = $config['attendance_weight'] ?? 0.25;
        $engagementWeight = $config['engagement_weight'] ?? 0.20;
        $growthWeight = $config['growth_weight'] ?? 0.20;
        $retentionWeight = $config['retention_weight'] ?? 0.20;
        $leadershipWeight = $config['leadership_weight'] ?? 0.15;

        // 1. Attendance Score (cluster meeting attendance)
        $attendanceScore = $this->calculateAttendanceScore($cluster, $members, $config);
        $factors['attendance'] = [
            'score' => round($attendanceScore, 2),
            'description' => 'Member attendance at cluster meetings',
        ];

        // 2. Engagement Score (average member lifecycle engagement)
        $engagementScore = $this->calculateEngagementScore($members);
        $factors['engagement'] = [
            'score' => round($engagementScore, 2),
            'description' => 'Average member engagement level',
        ];

        // 3. Growth Score (new members in last 90 days)
        $growthScore = $this->calculateGrowthScore($cluster, $members, $config);
        $factors['growth'] = [
            'score' => round($growthScore, 2),
            'description' => 'New member additions',
        ];

        // 4. Retention Score (members retained vs churned)
        $retentionScore = $this->calculateRetentionScore($members);
        $factors['retention'] = [
            'score' => round($retentionScore, 2),
            'description' => 'Member retention rate',
        ];

        // 5. Leadership Score (leader activity + meeting frequency)
        $leadershipScore = $this->calculateLeadershipScore($cluster, $config);
        $factors['leadership'] = [
            'score' => round($leadershipScore, 2),
            'description' => 'Leadership activity and meeting consistency',
        ];

        // Calculate overall score
        $overallScore = (
            ($attendanceScore * $attendanceWeight) +
            ($engagementScore * $engagementWeight) +
            ($growthScore * $growthWeight) +
            ($retentionScore * $retentionWeight) +
            ($leadershipScore * $leadershipWeight)
        );

        // Determine health level
        $level = ClusterHealthLevel::fromScore($overallScore);

        // Calculate trends
        $trends = $this->calculateTrends($cluster);

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $level,
            $attendanceScore,
            $engagementScore,
            $growthScore,
            $retentionScore,
            $leadershipScore
        );

        return new ClusterHealthAssessment(
            clusterId: (string) $cluster->id,
            clusterName: $cluster->name,
            overallScore: round($overallScore, 2),
            level: $level,
            attendanceScore: round($attendanceScore, 2),
            engagementScore: round($engagementScore, 2),
            growthScore: round($growthScore, 2),
            retentionScore: round($retentionScore, 2),
            leadershipScore: round($leadershipScore, 2),
            factors: $factors,
            recommendations: $recommendations,
            trends: $trends,
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Calculate attendance score for cluster meetings.
     */
    protected function calculateAttendanceScore(Cluster $cluster, Collection $members, array $config): float
    {
        // Get meetings in last 30 days
        $meetings = $cluster->meetings()
            ->where('meeting_date', '>=', now()->subDays(30))
            ->with('attendanceRecords')
            ->get();

        if ($meetings->isEmpty()) {
            // No meetings recorded - can't assess attendance
            return 50; // Neutral score
        }

        $totalAttendanceRate = 0;
        $meetingCount = $meetings->count();
        $memberCount = $members->count();

        foreach ($meetings as $meeting) {
            $attendedCount = $meeting->attendanceRecords
                ->where('attended', true)
                ->count();

            $attendanceRate = $memberCount > 0
                ? ($attendedCount / $memberCount) * 100
                : 0;

            $totalAttendanceRate += $attendanceRate;
        }

        return $meetingCount > 0 ? $totalAttendanceRate / $meetingCount : 50;
    }

    /**
     * Calculate engagement score based on member lifecycle stages.
     */
    protected function calculateEngagementScore(Collection $members): float
    {
        if ($members->isEmpty()) {
            return 0;
        }

        $totalScore = 0;

        foreach ($members as $member) {
            $stage = $member->lifecycle_stage;

            $stageScore = match ($stage) {
                LifecycleStage::Engaged => 100,
                LifecycleStage::Growing => 80,
                LifecycleStage::NewMember => 70,
                LifecycleStage::Prospect => 50,
                LifecycleStage::Disengaging => 30,
                LifecycleStage::AtRisk => 20,
                LifecycleStage::Dormant => 10,
                LifecycleStage::Inactive => 0,
                default => 50,
            };

            $totalScore += $stageScore;
        }

        return $totalScore / $members->count();
    }

    /**
     * Calculate growth score based on new members.
     */
    protected function calculateGrowthScore(Cluster $cluster, Collection $members, array $config): float
    {
        // Check for new members added to cluster in last 90 days
        $newMembers = $cluster->members()
            ->wherePivot('joined_at', '>=', now()->subDays(90))
            ->count();

        $memberCount = $members->count();

        if ($memberCount === 0) {
            return 0;
        }

        // Growth rate as percentage of current size
        $growthRate = ($newMembers / $memberCount) * 100;

        return match (true) {
            $growthRate >= 30 => 100, // 30%+ growth
            $growthRate >= 20 => 85,
            $growthRate >= 10 => 70,
            $growthRate >= 5 => 55,
            $growthRate > 0 => 40,
            default => 30, // No growth
        };
    }

    /**
     * Calculate retention score.
     */
    protected function calculateRetentionScore(Collection $members): float
    {
        if ($members->isEmpty()) {
            return 0;
        }

        // Count members who are at-risk or worse
        $atRiskCount = $members->filter(function ($member): bool {
            return in_array($member->lifecycle_stage, [
                LifecycleStage::AtRisk,
                LifecycleStage::Dormant,
                LifecycleStage::Inactive,
            ]);
        })->count();

        return (($members->count() - $atRiskCount) / $members->count()) * 100;
    }

    /**
     * Calculate leadership score.
     */
    protected function calculateLeadershipScore(Cluster $cluster, array $config): float
    {
        $score = 50; // Base score

        // Check if leader exists
        $leader = $cluster->leader;
        if (! $leader) {
            return 20; // No leader assigned
        }

        // Check leader's engagement
        $leaderStage = $leader->lifecycle_stage;
        $leaderEngaged = in_array($leaderStage, [
            LifecycleStage::Engaged,
            LifecycleStage::Growing,
        ]);

        if ($leaderEngaged) {
            $score += 20;
        }

        // Check meeting frequency
        $targetMeetings = $config['meeting_frequency_target'] ?? 4;
        $recentMeetings = $cluster->meetings()
            ->where('meeting_date', '>=', now()->subDays(30))
            ->count();

        $meetingRatio = min(1, $recentMeetings / $targetMeetings);
        $score += $meetingRatio * 30;

        return min(100, $score);
    }

    /**
     * Calculate trends for the cluster.
     *
     * @return array<string, mixed>
     */
    protected function calculateTrends(Cluster $cluster): array
    {
        // Compare this month vs last month
        $currentMonthMeetings = $cluster->meetings()
            ->whereBetween('meeting_date', [now()->startOfMonth(), now()])
            ->count();

        $lastMonthMeetings = $cluster->meetings()
            ->whereBetween('meeting_date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->count();

        $meetingTrend = $lastMonthMeetings > 0
            ? (($currentMonthMeetings - $lastMonthMeetings) / $lastMonthMeetings) * 100
            : ($currentMonthMeetings > 0 ? 100 : 0);

        $direction = match (true) {
            $meetingTrend > 10 => 'improving',
            $meetingTrend < -10 => 'declining',
            default => 'stable',
        };

        return [
            'direction' => $direction,
            'meeting_trend' => round($meetingTrend, 2),
            'current_month_meetings' => $currentMonthMeetings,
            'last_month_meetings' => $lastMonthMeetings,
        ];
    }

    /**
     * Generate recommendations based on health data.
     *
     * @return array<string>
     */
    protected function generateRecommendations(
        ClusterHealthLevel $level,
        float $attendanceScore,
        float $engagementScore,
        float $growthScore,
        float $retentionScore,
        float $leadershipScore
    ): array {
        $recommendations = [];

        // Level-based recommendations
        if ($level === ClusterHealthLevel::Critical) {
            $recommendations[] = 'Immediate leadership intervention required';
            $recommendations[] = 'Consider cluster restructuring or merger';
        } elseif ($level === ClusterHealthLevel::Struggling) {
            $recommendations[] = 'Schedule leadership meeting to address challenges';
        }

        // Score-based recommendations
        if ($attendanceScore < 40) {
            $recommendations[] = 'Improve meeting attendance - consider time/location changes';
        }

        if ($engagementScore < 40) {
            $recommendations[] = 'Focus on member engagement activities';
        }

        if ($growthScore < 40) {
            $recommendations[] = 'Develop outreach strategy for new member recruitment';
        }

        if ($retentionScore < 50) {
            $recommendations[] = 'Address member retention - follow up with at-risk members';
        }

        if ($leadershipScore < 40) {
            $recommendations[] = 'Strengthen leadership through training or co-leader assignment';
        }

        return array_slice($recommendations, 0, 4); // Max 4 recommendations
    }

    /**
     * Build assessment for empty cluster.
     */
    protected function buildEmptyClusterAssessment(Cluster $cluster): ClusterHealthAssessment
    {
        return new ClusterHealthAssessment(
            clusterId: (string) $cluster->id,
            clusterName: $cluster->name,
            overallScore: 0,
            level: ClusterHealthLevel::Critical,
            attendanceScore: 0,
            engagementScore: 0,
            growthScore: 0,
            retentionScore: 0,
            leadershipScore: 0,
            factors: ['member_count' => 0, 'note' => 'No members in cluster'],
            recommendations: ['Add members to the cluster', 'Assign cluster leadership'],
            trends: ['direction' => 'stable'],
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Get clusters needing attention.
     */
    public function getClustersNeedingAttention(string $branchId, int $limit = 20): Collection
    {
        return Cluster::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereIn('health_level', [
                ClusterHealthLevel::Struggling->value,
                ClusterHealthLevel::Critical->value,
            ])
            ->orderBy('health_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get thriving clusters.
     */
    public function getThrivingClusters(string $branchId, int $limit = 10): Collection
    {
        return Cluster::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('health_level', ClusterHealthLevel::Thriving->value)
            ->orderByDesc('health_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get health level distribution for a branch.
     *
     * @return array<string, int>
     */
    public function getHealthDistribution(string $branchId): array
    {
        $distribution = [];

        foreach (ClusterHealthLevel::cases() as $level) {
            $distribution[$level->value] = Cluster::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->where('health_level', $level->value)
                ->count();
        }

        return $distribution;
    }

    /**
     * Get overall branch cluster health summary.
     *
     * @return array<string, mixed>
     */
    public function getBranchHealthSummary(string $branchId): array
    {
        $clusters = Cluster::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNotNull('health_score')
            ->get();

        if ($clusters->isEmpty()) {
            return [
                'total_clusters' => 0,
                'average_health' => 0,
                'distribution' => [],
            ];
        }

        return [
            'total_clusters' => $clusters->count(),
            'average_health' => round($clusters->avg('health_score'), 2),
            'distribution' => $this->getHealthDistribution($branchId),
            'top_cluster' => $clusters->sortByDesc('health_score')->first()?->name,
            'needs_attention' => $clusters->filter(fn ($c): bool => $c->health_score < 40)->count(),
        ];
    }
}
