<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use Carbon\Carbon;

final readonly class AttendanceAnomaly
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public string $memberId,
        public string $memberName,
        public float $score,
        public float $baselineAttendance,
        public float $recentAttendance,
        public float $percentageChange,
        public array $factors,
        public ?Carbon $lastAttendanceDate = null,
    ) {}

    /**
     * Determine the severity of the anomaly.
     */
    public function severity(): string
    {
        return match (true) {
            $this->percentageChange <= -75 => 'critical',
            $this->percentageChange <= -50 => 'high',
            $this->percentageChange <= -25 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get the color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this->severity()) {
            'critical' => 'text-red-700',
            'high' => 'text-red-600',
            'medium' => 'text-amber-600',
            'low' => 'text-gray-500',
        };
    }

    /**
     * Get the badge variant for Flux UI.
     */
    public function badgeVariant(): string
    {
        return match ($this->severity()) {
            'critical', 'high' => 'danger',
            'medium' => 'warning',
            'low' => 'subtle',
        };
    }

    /**
     * Get a human-readable description of the change.
     */
    public function changeDescription(): string
    {
        $change = abs($this->percentageChange);

        return number_format($change, 0).'% decline in attendance';
    }

    /**
     * Get days since last attendance.
     */
    public function daysSinceLastAttendance(): ?int
    {
        if (!$this->lastAttendanceDate instanceof \Carbon\Carbon) {
            return null;
        }

        return (int) $this->lastAttendanceDate->diffInDays(now());
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
            'member_name' => $this->memberName,
            'score' => $this->score,
            'baseline_attendance' => $this->baselineAttendance,
            'recent_attendance' => $this->recentAttendance,
            'percentage_change' => $this->percentageChange,
            'factors' => $this->factors,
            'last_attendance_date' => $this->lastAttendanceDate?->toDateString(),
            'severity' => $this->severity(),
        ];
    }
}
