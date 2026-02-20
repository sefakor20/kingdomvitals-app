<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\ExperienceLevel;

readonly class MemberSuitabilityScore
{
    public function __construct(
        public string $memberId,
        public string $memberName,
        public float $totalScore,
        public array $factors,
        public bool $isAvailable,
        public array $warnings = [],
        public ?ExperienceLevel $experienceLevel = null,
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get the suitability level as a string.
     */
    public function suitabilityLevel(): string
    {
        return match (true) {
            $this->totalScore >= 80 => 'excellent',
            $this->totalScore >= 60 => 'good',
            $this->totalScore >= 40 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Get badge color for suitability level.
     */
    public function badgeColor(): string
    {
        return match ($this->suitabilityLevel()) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            default => 'zinc',
        };
    }

    /**
     * Check if member has warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if member is suitable for assignment.
     */
    public function isSuitable(): bool
    {
        return $this->isAvailable && $this->totalScore >= 30;
    }

    /**
     * Get the primary factor contributing to the score.
     */
    public function primaryFactor(): ?string
    {
        if ($this->factors === []) {
            return null;
        }

        $maxFactor = null;
        $maxValue = PHP_INT_MIN;

        foreach ($this->factors as $factor => $value) {
            if ($value > $maxValue) {
                $maxValue = $value;
                $maxFactor = $factor;
            }
        }

        return $maxFactor;
    }

    /**
     * Convert to array for storage/display.
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'member_name' => $this->memberName,
            'total_score' => $this->totalScore,
            'factors' => $this->factors,
            'is_available' => $this->isAvailable,
            'warnings' => $this->warnings,
            'experience_level' => $this->experienceLevel?->value,
            'suitability_level' => $this->suitabilityLevel(),
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: $data['member_id'],
            memberName: $data['member_name'],
            totalScore: (float) $data['total_score'],
            factors: $data['factors'] ?? [],
            isAvailable: (bool) $data['is_available'],
            warnings: $data['warnings'] ?? [],
            experienceLevel: isset($data['experience_level']) ? ExperienceLevel::from($data['experience_level']) : null,
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
