<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\PredictionTier;

final readonly class EventAttendancePrediction
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public string $memberId,
        public string $eventId,
        public float $probability,
        public PredictionTier $tier,
        public array $factors,
        public string $provider = 'heuristic',
    ) {}

    /**
     * Get the probability as a percentage string.
     */
    public function probabilityAsPercentage(): string
    {
        return number_format($this->probability, 0).'%';
    }

    /**
     * Determine if an invitation should be sent.
     */
    public function shouldSendInvitation(): bool
    {
        return $this->tier->shouldSendInvitation();
    }

    /**
     * Get the color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this->tier) {
            PredictionTier::High => 'text-green-600',
            PredictionTier::Medium => 'text-yellow-600',
            PredictionTier::Low => 'text-zinc-500',
        };
    }

    /**
     * Get the badge variant for Flux UI.
     */
    public function badgeVariant(): string
    {
        return match ($this->tier) {
            PredictionTier::High => 'success',
            PredictionTier::Medium => 'warning',
            PredictionTier::Low => 'default',
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
            'member_id' => $this->memberId,
            'event_id' => $this->eventId,
            'probability' => $this->probability,
            'tier' => $this->tier->value,
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
            memberId: $data['member_id'],
            eventId: $data['event_id'],
            probability: (float) $data['probability'],
            tier: PredictionTier::from($data['tier']),
            factors: $data['factors'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
        );
    }
}
