<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\HouseholdEngagementLevel;

readonly class HouseholdEngagementAssessment
{
    /**
     * @param  array<string, float>  $memberScores  Individual member contributions keyed by member ID
     * @param  array<string, mixed>  $factors  Scoring factors breakdown
     * @param  array<string>  $recommendations  Suggested actions
     */
    public function __construct(
        public string $householdId,
        public float $engagementScore,
        public HouseholdEngagementLevel $level,
        public float $attendanceScore,
        public float $givingScore,
        public float $memberVariance,
        public array $memberScores,
        public array $factors,
        public array $recommendations = [],
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Check if household is partially engaged (high variance in member scores).
     */
    public function isPartiallyEngaged(): bool
    {
        return $this->level === HouseholdEngagementLevel::PartiallyEngaged;
    }

    /**
     * Get member IDs with below-average engagement.
     *
     * @return array<string>
     */
    public function getDisengagedMembers(): array
    {
        if ($this->memberScores === []) {
            return [];
        }

        $average = array_sum($this->memberScores) / count($this->memberScores);
        $disengaged = [];

        foreach ($this->memberScores as $memberId => $score) {
            if ($score < $average * 0.6) { // 40% below average threshold
                $disengaged[] = $memberId;
            }
        }

        return $disengaged;
    }

    /**
     * Get the most engaged member ID.
     */
    public function getMostEngagedMember(): ?string
    {
        if ($this->memberScores === []) {
            return null;
        }

        return array_search(max($this->memberScores), $this->memberScores);
    }

    /**
     * Get badge color for the engagement level.
     */
    public function badgeColor(): string
    {
        return $this->level->color();
    }

    /**
     * Get icon for the engagement level.
     */
    public function icon(): string
    {
        return $this->level->icon();
    }

    /**
     * Check if household needs outreach.
     */
    public function needsOutreach(): bool
    {
        return $this->level->needsOutreach();
    }

    /**
     * Check if household is actively engaged.
     */
    public function isEngaged(): bool
    {
        return $this->level->isEngaged();
    }

    /**
     * Get the engagement gap description if partially engaged.
     */
    public function engagementGapDescription(): ?string
    {
        if (! $this->isPartiallyEngaged()) {
            return null;
        }

        $disengaged = $this->getDisengagedMembers();
        $count = count($disengaged);

        if ($count === 0) {
            return null;
        }

        return "{$count} member(s) have significantly lower engagement than the household average";
    }

    /**
     * Get the primary recommendation.
     */
    public function primaryRecommendation(): ?string
    {
        return $this->recommendations[0] ?? null;
    }

    /**
     * Get variance level as a string.
     */
    public function varianceLevel(): string
    {
        return match (true) {
            $this->memberVariance >= 40 => 'high',
            $this->memberVariance >= 20 => 'medium',
            default => 'low',
        };
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'household_id' => $this->householdId,
            'engagement_score' => $this->engagementScore,
            'level' => $this->level->value,
            'attendance_score' => $this->attendanceScore,
            'giving_score' => $this->givingScore,
            'member_variance' => $this->memberVariance,
            'member_scores' => $this->memberScores,
            'factors' => $this->factors,
            'recommendations' => $this->recommendations,
            'is_partially_engaged' => $this->isPartiallyEngaged(),
            'disengaged_members' => $this->getDisengagedMembers(),
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
            householdId: $data['household_id'],
            engagementScore: (float) $data['engagement_score'],
            level: HouseholdEngagementLevel::from($data['level']),
            attendanceScore: (float) ($data['attendance_score'] ?? 0),
            givingScore: (float) ($data['giving_score'] ?? 0),
            memberVariance: (float) ($data['member_variance'] ?? 0),
            memberScores: $data['member_scores'] ?? [],
            factors: $data['factors'] ?? [],
            recommendations: $data['recommendations'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
