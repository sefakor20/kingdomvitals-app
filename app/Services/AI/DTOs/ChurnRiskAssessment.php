<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

final readonly class ChurnRiskAssessment
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public float $score,
        public array $factors,
        public ?int $daysSinceLastDonation = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?int $tokensUsed = null,
    ) {}

    /**
     * Get the score as a percentage string.
     */
    public function scoreAsPercentage(): string
    {
        return number_format($this->score, 1).'%';
    }

    /**
     * Determine the risk level based on score.
     */
    public function riskLevel(): string
    {
        return match (true) {
            $this->score >= 70 => 'high',
            $this->score >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get the color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this->riskLevel()) {
            'high' => 'text-red-600',
            'medium' => 'text-amber-600',
            'low' => 'text-green-600',
        };
    }

    /**
     * Get the badge variant for Flux UI.
     */
    public function badgeVariant(): string
    {
        return match ($this->riskLevel()) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
        };
    }

    /**
     * Determine if this donor needs immediate attention.
     */
    public function needsAttention(): bool
    {
        return $this->score >= 70;
    }

    /**
     * Convert to array for JSON storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'factors' => $this->factors,
            'days_since_last_donation' => $this->daysSinceLastDonation,
            'provider' => $this->provider,
            'model' => $this->model,
            'tokens_used' => $this->tokensUsed,
        ];
    }
}
