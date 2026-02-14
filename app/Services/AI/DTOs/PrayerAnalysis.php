<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerUrgencyLevel;

readonly class PrayerAnalysis
{
    public function __construct(
        public PrayerUrgencyLevel $urgencyLevel,
        public float $priorityScore,
        public PrayerRequestCategory $suggestedCategory,
        public float $categoryConfidence,
        public array $detectedKeywords,
        public array $factors,
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get the badge color for the urgency level.
     */
    public function urgencyBadgeColor(): string
    {
        return $this->urgencyLevel->color();
    }

    /**
     * Check if this prayer request should be escalated.
     */
    public function shouldEscalate(): bool
    {
        return $this->urgencyLevel->shouldEscalate();
    }

    /**
     * Check if pastoral notification should be sent.
     */
    public function shouldNotifyPastor(): bool
    {
        return $this->urgencyLevel->shouldNotifyPastor();
    }

    /**
     * Get the priority level as a string.
     */
    public function priorityLevel(): string
    {
        return match (true) {
            $this->priorityScore >= 80 => 'critical',
            $this->priorityScore >= 60 => 'high',
            $this->priorityScore >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get priority badge color.
     */
    public function priorityBadgeColor(): string
    {
        return match ($this->priorityLevel()) {
            'critical' => 'red',
            'high' => 'amber',
            'medium' => 'yellow',
            default => 'zinc',
        };
    }

    /**
     * Check if category suggestion is confident.
     */
    public function isCategorySuggestionConfident(): bool
    {
        return $this->categoryConfidence >= 70;
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'urgency_level' => $this->urgencyLevel->value,
            'priority_score' => $this->priorityScore,
            'suggested_category' => $this->suggestedCategory->value,
            'category_confidence' => $this->categoryConfidence,
            'detected_keywords' => $this->detectedKeywords,
            'factors' => $this->factors,
            'provider' => $this->provider,
            'model' => $this->model,
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            urgencyLevel: PrayerUrgencyLevel::from($data['urgency_level']),
            priorityScore: (float) $data['priority_score'],
            suggestedCategory: PrayerRequestCategory::from($data['suggested_category']),
            categoryConfidence: (float) $data['category_confidence'],
            detectedKeywords: $data['detected_keywords'] ?? [],
            factors: $data['factors'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
