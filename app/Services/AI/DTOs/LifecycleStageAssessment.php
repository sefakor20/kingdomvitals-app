<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\LifecycleStage;

readonly class LifecycleStageAssessment
{
    public function __construct(
        public string $memberId,
        public LifecycleStage $stage,
        public ?LifecycleStage $previousStage,
        public float $confidenceScore,
        public array $factors,
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Check if the stage has changed from the previous assessment.
     */
    public function isTransition(): bool
    {
        return $this->previousStage !== null && $this->previousStage !== $this->stage;
    }

    /**
     * Get a description of the stage transition.
     */
    public function transitionDescription(): ?string
    {
        if (! $this->isTransition()) {
            return null;
        }

        return "Transitioned from {$this->previousStage->label()} to {$this->stage->label()}";
    }

    /**
     * Check if this is a concerning transition (moving to at-risk stages).
     */
    public function isConcerningTransition(): bool
    {
        if (! $this->isTransition()) {
            return false;
        }

        $concerningStages = [LifecycleStage::AtRisk, LifecycleStage::Dormant, LifecycleStage::Disengaging];

        return in_array($this->stage, $concerningStages, true)
            && ! in_array($this->previousStage, $concerningStages, true);
    }

    /**
     * Check if this is a positive transition (moving to engaged stages).
     */
    public function isPositiveTransition(): bool
    {
        if (! $this->isTransition()) {
            return false;
        }

        $engagedStages = [LifecycleStage::Growing, LifecycleStage::Engaged];

        return in_array($this->stage, $engagedStages, true)
            && ! in_array($this->previousStage, $engagedStages, true);
    }

    /**
     * Get badge color for the stage.
     */
    public function badgeColor(): string
    {
        return $this->stage->color();
    }

    /**
     * Get icon for the stage.
     */
    public function icon(): string
    {
        return $this->stage->icon();
    }

    /**
     * Check if member needs pastoral attention.
     */
    public function needsAttention(): bool
    {
        return $this->stage->needsAttention();
    }

    /**
     * Get recommended follow-up frequency in days.
     */
    public function followUpFrequencyDays(): int
    {
        return $this->stage->followUpFrequencyDays();
    }

    /**
     * Get confidence level as a string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidenceScore >= 80 => 'high',
            $this->confidenceScore >= 50 => 'medium',
            default => 'low',
        };
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'stage' => $this->stage->value,
            'previous_stage' => $this->previousStage?->value,
            'confidence_score' => $this->confidenceScore,
            'factors' => $this->factors,
            'is_transition' => $this->isTransition(),
            'transition_description' => $this->transitionDescription(),
            'provider' => $this->provider,
            'model' => $this->model,
            'assessed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: $data['member_id'],
            stage: LifecycleStage::from($data['stage']),
            previousStage: isset($data['previous_stage']) ? LifecycleStage::from($data['previous_stage']) : null,
            confidenceScore: (float) ($data['confidence_score'] ?? 50),
            factors: $data['factors'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
