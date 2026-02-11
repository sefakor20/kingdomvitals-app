<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\SmsEngagementLevel;

readonly class CampaignOptimizationResult
{
    /**
     * @param  array<string, array>  $segmentedRecipients  Keyed by engagement level
     * @param  array<string, array>  $optimalSendTimes  Best times per segment
     */
    public function __construct(
        public array $segmentedRecipients,
        public array $optimalSendTimes,
        public array $recommendations,
        public float $predictedEngagementRate,
        public int $totalRecipients,
        public array $segmentCounts,
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get recipients for a specific engagement level.
     */
    public function getRecipientsForLevel(SmsEngagementLevel $level): array
    {
        return $this->segmentedRecipients[$level->value] ?? [];
    }

    /**
     * Get the count for a specific segment.
     */
    public function getSegmentCount(SmsEngagementLevel $level): int
    {
        return $this->segmentCounts[$level->value] ?? 0;
    }

    /**
     * Get optimal send time for a segment.
     */
    public function getOptimalTimeForSegment(SmsEngagementLevel $level): ?array
    {
        return $this->optimalSendTimes[$level->value] ?? null;
    }

    /**
     * Get the percentage of engaged recipients (high + medium).
     */
    public function engagedPercentage(): float
    {
        if ($this->totalRecipients === 0) {
            return 0;
        }

        $engaged = ($this->segmentCounts['high'] ?? 0) + ($this->segmentCounts['medium'] ?? 0);

        return round(($engaged / $this->totalRecipients) * 100, 1);
    }

    /**
     * Get the percentage of at-risk recipients (low + inactive).
     */
    public function atRiskPercentage(): float
    {
        if ($this->totalRecipients === 0) {
            return 0;
        }

        $atRisk = ($this->segmentCounts['low'] ?? 0) + ($this->segmentCounts['inactive'] ?? 0);

        return round(($atRisk / $this->totalRecipients) * 100, 1);
    }

    /**
     * Check if campaign has significant at-risk audience.
     */
    public function hasSignificantAtRiskAudience(): bool
    {
        return $this->atRiskPercentage() > 30;
    }

    /**
     * Get the primary recommendation.
     */
    public function primaryRecommendation(): ?string
    {
        return $this->recommendations[0] ?? null;
    }

    /**
     * Get predicted engagement level as a string.
     */
    public function predictedEngagementLevel(): string
    {
        return match (true) {
            $this->predictedEngagementRate >= 70 => 'excellent',
            $this->predictedEngagementRate >= 50 => 'good',
            $this->predictedEngagementRate >= 30 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Get badge color for predicted engagement.
     */
    public function badgeColor(): string
    {
        return match ($this->predictedEngagementLevel()) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            default => 'red',
        };
    }

    /**
     * Convert to array for display.
     */
    public function toArray(): array
    {
        return [
            'segmented_recipients' => $this->segmentedRecipients,
            'optimal_send_times' => $this->optimalSendTimes,
            'recommendations' => $this->recommendations,
            'predicted_engagement_rate' => $this->predictedEngagementRate,
            'total_recipients' => $this->totalRecipients,
            'segment_counts' => $this->segmentCounts,
            'engaged_percentage' => $this->engagedPercentage(),
            'at_risk_percentage' => $this->atRiskPercentage(),
            'predicted_engagement_level' => $this->predictedEngagementLevel(),
            'provider' => $this->provider,
            'model' => $this->model,
            'optimized_at' => now()->toIso8601String(),
        ];
    }
}
