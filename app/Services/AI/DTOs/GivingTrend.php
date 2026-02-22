<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use Carbon\Carbon;

readonly class GivingTrend
{
    public function __construct(
        public string $memberId,
        public float $consistencyScore,
        public float $growthRate,
        public float $averageGift,
        public float $totalGiven,
        public int $donationCount,
        public float $donationsPerMonth,
        public string $donorTier,
        public ?Carbon $firstDonationDate,
        public ?Carbon $lastDonationDate,
        public int $daysSinceLastDonation,
        public float $largestGift,
        public ?DonationType $preferredType,
        public ?PaymentMethod $preferredMethod,
        public string $trend,
        public array $monthlyHistory,
        public int $confidenceScore,
    ) {}

    /**
     * Get the trend as a user-friendly label.
     */
    public function trendLabel(): string
    {
        return match ($this->trend) {
            'growing' => 'Growing',
            'stable' => 'Stable',
            'declining' => 'Declining',
            'new' => 'New Donor',
            'lapsed' => 'Lapsed',
            default => 'Unknown',
        };
    }

    /**
     * Get the trend icon name.
     */
    public function trendIcon(): string
    {
        return match ($this->trend) {
            'growing' => 'arrow-trending-up',
            'stable' => 'minus',
            'declining' => 'arrow-trending-down',
            'new' => 'star',
            'lapsed' => 'clock',
            default => 'question-mark-circle',
        };
    }

    /**
     * Get the trend color for UI display.
     */
    public function trendColor(): string
    {
        return match ($this->trend) {
            'growing' => 'green',
            'stable' => 'zinc',
            'declining' => 'red',
            'new' => 'purple',
            'lapsed' => 'amber',
            default => 'zinc',
        };
    }

    /**
     * Get the donor tier as a user-friendly label.
     */
    public function tierLabel(): string
    {
        return match ($this->donorTier) {
            'top_10' => 'Top 10%',
            'top_25' => 'Top 25%',
            'middle' => 'Middle 50%',
            'bottom' => 'Bottom 50%',
            default => 'Unknown',
        };
    }

    /**
     * Get the tier color for UI display.
     */
    public function tierColor(): string
    {
        return match ($this->donorTier) {
            'top_10' => 'purple',
            'top_25' => 'blue',
            'middle' => 'zinc',
            'bottom' => 'zinc',
            default => 'zinc',
        };
    }

    /**
     * Check if this is a major donor (top 10%).
     */
    public function isMajorDonor(): bool
    {
        return $this->donorTier === 'top_10';
    }

    /**
     * Check if this donor is declining significantly.
     */
    public function isDeclining(): bool
    {
        return $this->trend === 'declining';
    }

    /**
     * Check if this donor is growing.
     */
    public function isGrowing(): bool
    {
        return $this->trend === 'growing';
    }

    /**
     * Check if this is a new donor.
     */
    public function isNewDonor(): bool
    {
        return $this->trend === 'new';
    }

    /**
     * Check if this donor has lapsed.
     */
    public function isLapsed(): bool
    {
        return $this->trend === 'lapsed';
    }

    /**
     * Get the confidence level as a string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidenceScore >= 80 => 'high',
            $this->confidenceScore >= 60 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get the badge color for confidence.
     */
    public function confidenceBadgeColor(): string
    {
        return match ($this->confidenceLevel()) {
            'high' => 'green',
            'medium' => 'amber',
            default => 'zinc',
        };
    }

    /**
     * Get formatted growth rate with sign.
     */
    public function formattedGrowthRate(): string
    {
        $sign = $this->growthRate >= 0 ? '+' : '';

        return $sign.number_format($this->growthRate, 1).'%';
    }

    /**
     * Get the last 6 months of giving totals.
     */
    public function recentMonthlyTotals(): array
    {
        return array_slice($this->monthlyHistory, -6, 6, true);
    }

    /**
     * Get the total giving in the last 6 months.
     */
    public function recentTotal(): float
    {
        return array_sum($this->recentMonthlyTotals());
    }

    /**
     * Get the preferred donation type label.
     */
    public function preferredTypeLabel(): ?string
    {
        return $this->preferredType?->name;
    }

    /**
     * Get the preferred payment method label.
     */
    public function preferredMethodLabel(): ?string
    {
        return $this->preferredMethod?->name;
    }

    /**
     * Check if the donor requires attention (declining or lapsed).
     */
    public function requiresAttention(): bool
    {
        if ($this->isDeclining()) {
            return true;
        }

        return $this->isLapsed();
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'consistency_score' => $this->consistencyScore,
            'growth_rate' => $this->growthRate,
            'average_gift' => $this->averageGift,
            'total_given' => $this->totalGiven,
            'donation_count' => $this->donationCount,
            'donations_per_month' => $this->donationsPerMonth,
            'donor_tier' => $this->donorTier,
            'first_donation_date' => $this->firstDonationDate?->toDateString(),
            'last_donation_date' => $this->lastDonationDate?->toDateString(),
            'days_since_last_donation' => $this->daysSinceLastDonation,
            'largest_gift' => $this->largestGift,
            'preferred_type' => $this->preferredType?->value,
            'preferred_method' => $this->preferredMethod?->value,
            'trend' => $this->trend,
            'monthly_history' => $this->monthlyHistory,
            'confidence_score' => $this->confidenceScore,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: $data['member_id'],
            consistencyScore: (float) $data['consistency_score'],
            growthRate: (float) $data['growth_rate'],
            averageGift: (float) $data['average_gift'],
            totalGiven: (float) $data['total_given'],
            donationCount: (int) $data['donation_count'],
            donationsPerMonth: (float) $data['donations_per_month'],
            donorTier: $data['donor_tier'],
            firstDonationDate: isset($data['first_donation_date']) ? Carbon::parse($data['first_donation_date']) : null,
            lastDonationDate: isset($data['last_donation_date']) ? Carbon::parse($data['last_donation_date']) : null,
            daysSinceLastDonation: (int) $data['days_since_last_donation'],
            largestGift: (float) $data['largest_gift'],
            preferredType: isset($data['preferred_type']) ? DonationType::tryFrom($data['preferred_type']) : null,
            preferredMethod: isset($data['preferred_method']) ? PaymentMethod::tryFrom($data['preferred_method']) : null,
            trend: $data['trend'],
            monthlyHistory: $data['monthly_history'] ?? [],
            confidenceScore: (int) $data['confidence_score'],
        );
    }
}
