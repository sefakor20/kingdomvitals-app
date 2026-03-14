<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\RiskLevel;
use Carbon\Carbon;

final readonly class PledgeFulfillmentPrediction
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public string $pledgeId,
        public string $memberId,
        public float $fulfillmentProbability,
        public RiskLevel $riskLevel,
        public ?Carbon $recommendedNudgeAt,
        public array $factors,
        public string $provider = 'heuristic',
    ) {}

    /**
     * Get the probability as a percentage string.
     */
    public function probabilityAsPercentage(): string
    {
        return number_format($this->fulfillmentProbability, 0).'%';
    }

    /**
     * Determine if a nudge should be sent.
     */
    public function shouldSendNudge(): bool
    {
        return $this->riskLevel->shouldSendNudge();
    }

    /**
     * Get the color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this->riskLevel) {
            RiskLevel::High => 'text-red-600',
            RiskLevel::Medium => 'text-yellow-600',
            RiskLevel::Low => 'text-green-600',
        };
    }

    /**
     * Get the badge variant for Flux UI.
     */
    public function badgeVariant(): string
    {
        return match ($this->riskLevel) {
            RiskLevel::High => 'danger',
            RiskLevel::Medium => 'warning',
            RiskLevel::Low => 'success',
        };
    }

    /**
     * Convert to array for JSON storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pledge_id' => $this->pledgeId,
            'member_id' => $this->memberId,
            'fulfillment_probability' => $this->fulfillmentProbability,
            'risk_level' => $this->riskLevel->value,
            'recommended_nudge_at' => $this->recommendedNudgeAt?->toIso8601String(),
            'factors' => $this->factors,
            'provider' => $this->provider,
        ];
    }

    /**
     * Create from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            pledgeId: $data['pledge_id'],
            memberId: $data['member_id'],
            fulfillmentProbability: (float) $data['fulfillment_probability'],
            riskLevel: RiskLevel::from($data['risk_level']),
            recommendedNudgeAt: isset($data['recommended_nudge_at']) ? Carbon::parse($data['recommended_nudge_at']) : null,
            factors: $data['factors'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
        );
    }
}
