<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\SmsEngagementLevel;

readonly class SmsEngagementProfile
{
    public function __construct(
        public string $memberId,
        public float $engagementScore,
        public SmsEngagementLevel $engagementLevel,
        public ?int $optimalSendHour,
        public ?int $optimalSendDay,
        public float $responseRate,
        public array $factors,
        public array $recommendations = [],
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get badge color for engagement level.
     */
    public function badgeColor(): string
    {
        return $this->engagementLevel->color();
    }

    /**
     * Check if member is actively engaged.
     */
    public function isEngaged(): bool
    {
        return $this->engagementLevel->isEngaged();
    }

    /**
     * Check if messaging frequency should be reduced.
     */
    public function shouldReduceFrequency(): bool
    {
        return $this->engagementLevel->shouldReduceFrequency();
    }

    /**
     * Get recommended monthly message count.
     */
    public function recommendedMonthlyMessages(): int
    {
        return $this->engagementLevel->recommendedMonthlyMessages();
    }

    /**
     * Get optimal send time as a human-readable string.
     */
    public function optimalSendTimeString(): ?string
    {
        if ($this->optimalSendHour === null) {
            return null;
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $day = $this->optimalSendDay !== null ? $dayNames[$this->optimalSendDay] : 'Any day';

        $hour = $this->optimalSendHour;
        $ampm = $hour >= 12 ? 'PM' : 'AM';
        $hour12 = $hour % 12 ?: 12;

        return "{$day} at {$hour12}:00 {$ampm}";
    }

    /**
     * Get the time slot for the optimal send hour.
     */
    public function optimalTimeSlot(): string
    {
        if ($this->optimalSendHour === null) {
            return 'unknown';
        }

        return match (true) {
            $this->optimalSendHour >= 5 && $this->optimalSendHour < 12 => 'morning',
            $this->optimalSendHour >= 12 && $this->optimalSendHour < 17 => 'afternoon',
            $this->optimalSendHour >= 17 && $this->optimalSendHour < 21 => 'evening',
            default => 'night',
        };
    }

    /**
     * Check if a given hour is optimal (within 2 hours of optimal time).
     */
    public function isOptimalHour(int $hour): bool
    {
        if ($this->optimalSendHour === null) {
            return true;
        }

        $diff = abs($this->optimalSendHour - $hour);

        return $diff <= 2 || $diff >= 22;
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'engagement_score' => $this->engagementScore,
            'engagement_level' => $this->engagementLevel->value,
            'optimal_send_hour' => $this->optimalSendHour,
            'optimal_send_day' => $this->optimalSendDay,
            'response_rate' => $this->responseRate,
            'factors' => $this->factors,
            'recommendations' => $this->recommendations,
            'provider' => $this->provider,
            'model' => $this->model,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: $data['member_id'],
            engagementScore: (float) $data['engagement_score'],
            engagementLevel: SmsEngagementLevel::from($data['engagement_level']),
            optimalSendHour: $data['optimal_send_hour'],
            optimalSendDay: $data['optimal_send_day'],
            responseRate: (float) ($data['response_rate'] ?? 0),
            factors: $data['factors'] ?? [],
            recommendations: $data['recommendations'] ?? [],
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
