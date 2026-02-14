<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use Carbon\Carbon;

readonly class AttendanceForecast
{
    public function __construct(
        public string $serviceId,
        public string $serviceName,
        public Carbon $forecastDate,
        public int $predictedAttendance,
        public int $predictedMembers,
        public int $predictedVisitors,
        public float $confidence,
        public array $factors,
        public ?int $capacityPercent = null,
    ) {}

    /**
     * Get the confidence level as a string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidence >= 80 => 'high',
            $this->confidence >= 50 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get the color class for the confidence badge.
     */
    public function confidenceColorClass(): string
    {
        return match ($this->confidenceLevel()) {
            'high' => 'text-green-600 dark:text-green-400',
            'medium' => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-zinc-600 dark:text-zinc-400',
        };
    }

    /**
     * Get the badge color for Flux UI.
     */
    public function confidenceBadgeColor(): string
    {
        return match ($this->confidenceLevel()) {
            'high' => 'green',
            'medium' => 'yellow',
            default => 'zinc',
        };
    }

    /**
     * Check if this forecast is for a date in the past.
     */
    public function isPast(): bool
    {
        return $this->forecastDate->isPast();
    }

    /**
     * Check if this is for today.
     */
    public function isToday(): bool
    {
        return $this->forecastDate->isToday();
    }

    /**
     * Get formatted date for display.
     */
    public function formattedDate(): string
    {
        if ($this->isToday()) {
            return __('Today');
        }

        if ($this->forecastDate->isNextWeek()) {
            return $this->forecastDate->format('l'); // Day name only for next week
        }

        return $this->forecastDate->format('M d');
    }

    /**
     * Get the day of week name.
     */
    public function dayOfWeek(): string
    {
        return $this->forecastDate->format('l');
    }

    /**
     * Get key factors as a formatted list.
     */
    public function keyFactors(): array
    {
        return array_slice($this->factors, 0, 3);
    }

    /**
     * Check if capacity is at risk (>85%).
     */
    public function isNearCapacity(): bool
    {
        return $this->capacityPercent !== null && $this->capacityPercent >= 85;
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'forecast_date' => $this->forecastDate->toDateString(),
            'predicted_attendance' => $this->predictedAttendance,
            'predicted_members' => $this->predictedMembers,
            'predicted_visitors' => $this->predictedVisitors,
            'confidence' => $this->confidence,
            'factors' => $this->factors,
            'capacity_percent' => $this->capacityPercent,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            serviceId: $data['service_id'],
            serviceName: $data['service_name'],
            forecastDate: Carbon::parse($data['forecast_date']),
            predictedAttendance: (int) $data['predicted_attendance'],
            predictedMembers: (int) $data['predicted_members'],
            predictedVisitors: (int) $data['predicted_visitors'],
            confidence: (float) $data['confidence'],
            factors: $data['factors'] ?? [],
            capacityPercent: $data['capacity_percent'] ?? null,
        );
    }
}
