<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\HouseholdEngagementAssessment;
use Illuminate\Support\Collection;

class HouseholdEngagementService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Calculate engagement assessment for a household.
     */
    public function calculateEngagement(Household $household): HouseholdEngagementAssessment
    {
        return $this->calculateHeuristicEngagement($household);
    }

    /**
     * Calculate engagement using heuristic algorithm.
     */
    protected function calculateHeuristicEngagement(Household $household): HouseholdEngagementAssessment
    {
        $config = config('ai.scoring.household', []);
        $factors = [];
        $memberScores = [];
        $recommendations = [];

        // Get household members
        $members = $household->members()->get();

        if ($members->isEmpty()) {
            return $this->buildEmptyHouseholdAssessment($household);
        }

        // Calculate individual member scores
        $headId = $household->head_id;
        $headBonus = $config['head_member_bonus'] ?? 1.2;

        foreach ($members as $member) {
            $memberScore = $this->calculateMemberEngagementScore($member, $config);
            $weight = $member->id === $headId ? $headBonus : 1.0;
            $memberScores[(string) $member->id] = [
                'score' => $memberScore,
                'weight' => $weight,
                'weighted_score' => $memberScore * $weight,
            ];
        }

        // Calculate weighted average
        $totalWeight = array_sum(array_column($memberScores, 'weight'));
        $totalWeightedScore = array_sum(array_column($memberScores, 'weighted_score'));
        $engagementScore = $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;

        // Calculate individual component scores
        $attendanceScore = $this->calculateHouseholdAttendanceScore($members, $config);
        $givingScore = $this->calculateHouseholdGivingScore($members, $config);

        // Calculate variance among member scores
        $rawScores = array_column($memberScores, 'score');
        $memberVariance = $this->calculateVariance($rawScores);

        // Determine engagement level
        $varianceThreshold = $config['variance_threshold'] ?? 30;
        $level = HouseholdEngagementLevel::fromScoreAndVariance(
            $engagementScore,
            $memberVariance,
            $varianceThreshold
        );

        // Build factors
        $factors = [
            'member_count' => $members->count(),
            'attendance_score' => round($attendanceScore, 2),
            'giving_score' => round($givingScore, 2),
            'member_variance' => round($memberVariance, 2),
            'has_head' => $headId !== null,
        ];

        // Generate recommendations
        $recommendations = $this->generateRecommendations($level, $memberVariance, $attendanceScore, $givingScore, $memberScores);

        // Simplify memberScores for storage
        $simplifiedMemberScores = [];
        foreach ($memberScores as $memberId => $data) {
            $simplifiedMemberScores[$memberId] = $data['score'];
        }

        return new HouseholdEngagementAssessment(
            householdId: (string) $household->id,
            engagementScore: round($engagementScore, 2),
            level: $level,
            attendanceScore: round($attendanceScore, 2),
            givingScore: round($givingScore, 2),
            memberVariance: round($memberVariance, 2),
            memberScores: $simplifiedMemberScores,
            factors: $factors,
            recommendations: $recommendations,
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Calculate engagement score for an individual member.
     */
    protected function calculateMemberEngagementScore(Member $member, array $config): float
    {
        $attendanceWeight = $config['attendance_weight'] ?? 0.40;
        $givingWeight = $config['giving_weight'] ?? 0.30;
        $lifecycleWeight = $config['lifecycle_weight'] ?? 0.20;
        $smsWeight = $config['sms_engagement_weight'] ?? 0.10;

        $score = 0;

        // Attendance component (0-100)
        $attendanceScore = $this->calculateMemberAttendanceScore($member);
        $score += $attendanceScore * $attendanceWeight;

        // Giving component (0-100)
        $givingScore = $this->calculateMemberGivingScore($member);
        $score += $givingScore * $givingWeight;

        // Lifecycle stage component (0-100)
        $lifecycleScore = $this->getLifecycleStageScore($member);
        $score += $lifecycleScore * $lifecycleWeight;

        // SMS engagement component (0-100)
        $smsScore = $member->sms_engagement_score ?? 50;
        $score += $smsScore * $smsWeight;

        return min(100, max(0, $score));
    }

    /**
     * Calculate attendance score for a member.
     */
    protected function calculateMemberAttendanceScore(Member $member): float
    {
        // Get attendance in last 90 days
        $recentCount = $member->attendance()
            ->where('date', '>=', now()->subDays(90))
            ->count();

        // Assume 12 possible attendance opportunities in 90 days (weekly)
        $maxExpected = 12;
        $attendanceRate = min(1, $recentCount / $maxExpected);

        return $attendanceRate * 100;
    }

    /**
     * Calculate giving score for a member.
     */
    protected function calculateMemberGivingScore(Member $member): float
    {
        // Check for donations in last 90 days
        $hasDonation = $member->donations()
            ->where('donation_date', '>=', now()->subDays(90))
            ->exists();

        if (! $hasDonation) {
            return 30; // Base score for non-givers
        }

        // Get donation count
        $donationCount = $member->donations()
            ->where('donation_date', '>=', now()->subDays(90))
            ->count();

        // Score based on donation frequency
        return match (true) {
            $donationCount >= 6 => 100,
            $donationCount >= 3 => 80,
            $donationCount >= 1 => 60,
            default => 30,
        };
    }

    /**
     * Get lifecycle stage score for a member.
     */
    protected function getLifecycleStageScore(Member $member): float
    {
        $stage = $member->lifecycle_stage;

        if (! $stage) {
            return 50; // Default
        }

        return match ($stage) {
            LifecycleStage::Engaged => 100,
            LifecycleStage::Growing => 80,
            LifecycleStage::NewMember => 70,
            LifecycleStage::Prospect => 50,
            LifecycleStage::Disengaging => 40,
            LifecycleStage::AtRisk => 25,
            LifecycleStage::Dormant => 15,
            LifecycleStage::Inactive => 0,
        };
    }

    /**
     * Calculate household attendance score.
     */
    protected function calculateHouseholdAttendanceScore(Collection $members, array $config): float
    {
        if ($members->isEmpty()) {
            return 0;
        }

        $totalScore = 0;
        foreach ($members as $member) {
            $totalScore += $this->calculateMemberAttendanceScore($member);
        }

        return $totalScore / $members->count();
    }

    /**
     * Calculate household giving score.
     */
    protected function calculateHouseholdGivingScore(Collection $members, array $config): float
    {
        if ($members->isEmpty()) {
            return 0;
        }

        // For giving, we count household-level (any member giving counts)
        $totalDonations = 0;
        foreach ($members as $member) {
            $totalDonations += $member->donations()
                ->where('donation_date', '>=', now()->subDays(90))
                ->count();
        }

        // Score based on household giving frequency
        return match (true) {
            $totalDonations >= 12 => 100,
            $totalDonations >= 6 => 80,
            $totalDonations >= 3 => 60,
            $totalDonations >= 1 => 40,
            default => 20,
        };
    }

    /**
     * Calculate variance among scores.
     */
    protected function calculateVariance(array $scores): float
    {
        if (count($scores) < 2) {
            return 0;
        }

        $mean = array_sum($scores) / count($scores);
        $sumSquaredDiff = 0;

        foreach ($scores as $score) {
            $sumSquaredDiff += pow($score - $mean, 2);
        }

        return sqrt($sumSquaredDiff / count($scores));
    }

    /**
     * Generate recommendations based on engagement data.
     *
     * @return array<string>
     */
    protected function generateRecommendations(
        HouseholdEngagementLevel $level,
        float $variance,
        float $attendanceScore,
        float $givingScore,
        array $memberScores
    ): array {
        $recommendations = [];

        // Level-based recommendations
        if ($level === HouseholdEngagementLevel::Disengaged) {
            $recommendations[] = 'Schedule a home visit to reconnect with the family';
            $recommendations[] = 'Send a personalized outreach message';
        } elseif ($level === HouseholdEngagementLevel::Low) {
            $recommendations[] = 'Invite the household to an upcoming church event';
            $recommendations[] = 'Assign a care group leader to follow up';
        } elseif ($level === HouseholdEngagementLevel::PartiallyEngaged) {
            $recommendations[] = 'Focus engagement efforts on less active household members';
            $recommendations[] = 'Consider family-oriented activities to encourage full household participation';
        }

        // Score-based recommendations
        if ($attendanceScore < 30) {
            $recommendations[] = 'Address attendance barriers - consider transportation or scheduling issues';
        }

        if ($givingScore < 30) {
            $recommendations[] = 'Educate about giving opportunities and stewardship';
        }

        // Variance-based recommendations
        if ($variance > 40) {
            $recommendations[] = 'Large engagement gap within household - personalized outreach to less engaged members';
        }

        return array_slice($recommendations, 0, 3); // Max 3 recommendations
    }

    /**
     * Build assessment for empty household.
     */
    protected function buildEmptyHouseholdAssessment(Household $household): HouseholdEngagementAssessment
    {
        return new HouseholdEngagementAssessment(
            householdId: (string) $household->id,
            engagementScore: 0,
            level: HouseholdEngagementLevel::Disengaged,
            attendanceScore: 0,
            givingScore: 0,
            memberVariance: 0,
            memberScores: [],
            factors: ['member_count' => 0, 'note' => 'No members in household'],
            recommendations: ['Add members to the household'],
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Get households needing outreach.
     */
    public function getHouseholdsNeedingOutreach(string $branchId, int $limit = 30): Collection
    {
        return Household::query()
            ->where('branch_id', $branchId)
            ->whereIn('engagement_level', [
                HouseholdEngagementLevel::Low->value,
                HouseholdEngagementLevel::Disengaged->value,
                HouseholdEngagementLevel::PartiallyEngaged->value,
            ])
            ->orderBy('engagement_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get partially engaged households.
     */
    public function getPartiallyEngagedHouseholds(string $branchId, int $limit = 20): Collection
    {
        return Household::query()
            ->where('branch_id', $branchId)
            ->where('engagement_level', HouseholdEngagementLevel::PartiallyEngaged->value)
            ->orderByDesc('member_engagement_variance')
            ->limit($limit)
            ->get();
    }

    /**
     * Get engagement level distribution for a branch.
     *
     * @return array<string, int>
     */
    public function getEngagementDistribution(string $branchId): array
    {
        $distribution = [];

        foreach (HouseholdEngagementLevel::cases() as $level) {
            $distribution[$level->value] = Household::query()
                ->where('branch_id', $branchId)
                ->where('engagement_level', $level->value)
                ->count();
        }

        return $distribution;
    }
}
