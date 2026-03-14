<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

final readonly class GivingCapacityAssessment
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public string $memberId,
        public float $capacityScore,
        public float $currentAnnualGiving,
        public float $potentialGap,
        public array $factors,
        public string $provider = 'heuristic',
    ) {}

    /**
     * Get the capacity score as a percentage string.
     */
    public function capacityAsPercentage(): string
    {
        return number_format($this->capacityScore, 0).'%';
    }

    /**
     * Get the potential gap formatted as currency.
     */
    public function formattedPotentialGap(): string
    {
        return number_format($this->potentialGap, 2);
    }

    /**
     * Determine if this member has significant untapped potential.
     */
    public function hasUntappedPotential(): bool
    {
        return $this->potentialGap > 0 && $this->capacityScore < 60;
    }

    /**
     * Get the capacity level label.
     */
    public function capacityLevel(): string
    {
        return match (true) {
            $this->capacityScore >= 80 => 'maximized',
            $this->capacityScore >= 60 => 'engaged',
            $this->capacityScore >= 40 => 'developing',
            $this->capacityScore >= 20 => 'underdeveloped',
            default => 'untapped',
        };
    }

    /**
     * Get the color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this->capacityLevel()) {
            'maximized' => 'text-green-600',
            'engaged' => 'text-emerald-600',
            'developing' => 'text-yellow-600',
            'underdeveloped' => 'text-amber-600',
            'untapped' => 'text-red-600',
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
            'capacity_score' => $this->capacityScore,
            'current_annual_giving' => $this->currentAnnualGiving,
            'potential_gap' => $this->potentialGap,
            'factors' => $this->factors,
            'provider' => $this->provider,
        ];
    }
}
