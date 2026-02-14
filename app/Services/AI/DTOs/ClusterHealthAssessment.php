<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\ClusterHealthLevel;

readonly class ClusterHealthAssessment
{
    /**
     * @param  array<string, mixed>  $factors  Scoring factors breakdown
     * @param  array<string>  $recommendations  Suggested actions
     * @param  array<string, mixed>  $trends  Historical trend data
     */
    public function __construct(
        public string $clusterId,
        public string $clusterName,
        public float $overallScore,
        public ClusterHealthLevel $level,
        public float $attendanceScore,
        public float $engagementScore,
        public float $growthScore,
        public float $retentionScore,
        public float $leadershipScore,
        public array $factors,
        public array $recommendations = [],
        public array $trends = [],
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Check if cluster needs leadership attention.
     */
    public function needsAttention(): bool
    {
        return $this->level->needsAttention();
    }

    /**
     * Check if cluster is thriving.
     */
    public function isThriving(): bool
    {
        return $this->level === ClusterHealthLevel::Thriving;
    }

    /**
     * Check if cluster is performing well.
     */
    public function isPerformingWell(): bool
    {
        return $this->level->isPerformingWell();
    }

    /**
     * Get top concerns (lowest scoring areas).
     *
     * @return array<string, float>
     */
    public function getTopConcerns(): array
    {
        $scores = [
            'attendance' => $this->attendanceScore,
            'engagement' => $this->engagementScore,
            'growth' => $this->growthScore,
            'retention' => $this->retentionScore,
            'leadership' => $this->leadershipScore,
        ];

        asort($scores);

        // Return bottom 2 scores that are below 50
        $concerns = [];
        foreach ($scores as $area => $score) {
            if ($score < 50 && count($concerns) < 2) {
                $concerns[$area] = $score;
            }
        }

        return $concerns;
    }

    /**
     * Get strengths (highest scoring areas).
     *
     * @return array<string, float>
     */
    public function getStrengths(): array
    {
        $scores = [
            'attendance' => $this->attendanceScore,
            'engagement' => $this->engagementScore,
            'growth' => $this->growthScore,
            'retention' => $this->retentionScore,
            'leadership' => $this->leadershipScore,
        ];

        arsort($scores);

        // Return top 2 scores that are above 60
        $strengths = [];
        foreach ($scores as $area => $score) {
            if ($score >= 60 && count($strengths) < 2) {
                $strengths[$area] = $score;
            }
        }

        return $strengths;
    }

    /**
     * Get the weakest area.
     */
    public function getWeakestArea(): string
    {
        $scores = [
            'attendance' => $this->attendanceScore,
            'engagement' => $this->engagementScore,
            'growth' => $this->growthScore,
            'retention' => $this->retentionScore,
            'leadership' => $this->leadershipScore,
        ];

        return array_search(min($scores), $scores);
    }

    /**
     * Get the strongest area.
     */
    public function getStrongestArea(): string
    {
        $scores = [
            'attendance' => $this->attendanceScore,
            'engagement' => $this->engagementScore,
            'growth' => $this->growthScore,
            'retention' => $this->retentionScore,
            'leadership' => $this->leadershipScore,
        ];

        return array_search(max($scores), $scores);
    }

    /**
     * Get badge color for the health level.
     */
    public function badgeColor(): string
    {
        return $this->level->color();
    }

    /**
     * Get icon for the health level.
     */
    public function icon(): string
    {
        return $this->level->icon();
    }

    /**
     * Get recommended check-in frequency in days.
     */
    public function checkInFrequencyDays(): int
    {
        return $this->level->checkInFrequencyDays();
    }

    /**
     * Get intervention priority (higher = more urgent).
     */
    public function interventionPriority(): int
    {
        return $this->level->interventionPriority();
    }

    /**
     * Get the primary recommendation.
     */
    public function primaryRecommendation(): ?string
    {
        return $this->recommendations[0] ?? null;
    }

    /**
     * Check if there's a declining trend.
     */
    public function isDeclining(): bool
    {
        return ($this->trends['direction'] ?? null) === 'declining';
    }

    /**
     * Check if there's an improving trend.
     */
    public function isImproving(): bool
    {
        return ($this->trends['direction'] ?? null) === 'improving';
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'cluster_id' => $this->clusterId,
            'cluster_name' => $this->clusterName,
            'overall_score' => $this->overallScore,
            'level' => $this->level->value,
            'attendance_score' => $this->attendanceScore,
            'engagement_score' => $this->engagementScore,
            'growth_score' => $this->growthScore,
            'retention_score' => $this->retentionScore,
            'leadership_score' => $this->leadershipScore,
            'factors' => $this->factors,
            'recommendations' => $this->recommendations,
            'trends' => $this->trends,
            'top_concerns' => $this->getTopConcerns(),
            'strengths' => $this->getStrengths(),
            'needs_attention' => $this->needsAttention(),
            'provider' => $this->provider,
            'model' => $this->model,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'],
            clusterName: $data['cluster_name'] ?? '',
            overallScore: (float) $data['overall_score'],
            level: ClusterHealthLevel::from($data['level']),
            attendanceScore: (float) ($data['attendance_score'] ?? 0),
            engagementScore: (float) ($data['engagement_score'] ?? 0),
            growthScore: (float) ($data['growth_score'] ?? 0),
            retentionScore: (float) ($data['retention_score'] ?? 0),
            leadershipScore: (float) ($data['leadership_score'] ?? 0),
            factors: $data['factors'] ?? [],
            recommendations: $data['recommendations'] ?? [],
            trends: $data['trends'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
