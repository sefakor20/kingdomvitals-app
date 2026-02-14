<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use Carbon\Carbon;

readonly class FinancialForecast
{
    public function __construct(
        public string $branchId,
        public string $forecastType,
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public float $predictedTotal,
        public float $predictedTithes,
        public float $predictedOfferings,
        public float $predictedSpecial,
        public float $predictedOther,
        public float $confidenceLower,
        public float $confidenceUpper,
        public float $confidence,
        public array $factors,
        public array $cohortBreakdown,
        public ?float $budgetTarget = null,
    ) {}

    /**
     * Get the confidence level as a string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidence >= 80 => 'high',
            $this->confidence >= 60 => 'medium',
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
            'medium' => 'text-amber-600 dark:text-amber-400',
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
            'medium' => 'amber',
            default => 'zinc',
        };
    }

    /**
     * Get the gap amount vs budget.
     */
    public function gapAmount(): ?float
    {
        if ($this->budgetTarget === null) {
            return null;
        }

        return $this->predictedTotal - $this->budgetTarget;
    }

    /**
     * Get the gap percentage vs budget.
     */
    public function gapPercentage(): ?float
    {
        if ($this->budgetTarget === null || $this->budgetTarget == 0) {
            return null;
        }

        return round((($this->predictedTotal - $this->budgetTarget) / $this->budgetTarget) * 100, 1);
    }

    /**
     * Check if forecast meets or exceeds budget target.
     */
    public function isOnTrack(): ?bool
    {
        if ($this->budgetTarget === null) {
            return null;
        }

        return $this->predictedTotal >= $this->budgetTarget;
    }

    /**
     * Get the formatted period label.
     */
    public function periodLabel(): string
    {
        if ($this->forecastType === 'weekly') {
            return $this->periodStart->format('M j').' - '.$this->periodEnd->format('M j, Y');
        }

        if ($this->forecastType === 'monthly') {
            return $this->periodStart->format('F Y');
        }

        // Quarterly
        $quarter = (int) ceil($this->periodStart->month / 3);

        return 'Q'.$quarter.' '.$this->periodStart->format('Y');
    }

    /**
     * Check if this is a weekly forecast.
     */
    public function isWeekly(): bool
    {
        return $this->forecastType === 'weekly';
    }

    /**
     * Check if this is a monthly forecast.
     */
    public function isMonthly(): bool
    {
        return $this->forecastType === 'monthly';
    }

    /**
     * Check if this is a quarterly forecast.
     */
    public function isQuarterly(): bool
    {
        return $this->forecastType === 'quarterly';
    }

    /**
     * Get the confidence range as a formatted string.
     */
    public function confidenceRange(): string
    {
        return number_format($this->confidenceLower, 2).' - '.number_format($this->confidenceUpper, 2);
    }

    /**
     * Get key factors affecting the forecast.
     */
    public function keyFactors(): array
    {
        return array_slice($this->factors, 0, 3);
    }

    /**
     * Get the giving breakdown as percentages.
     */
    public function givingBreakdown(): array
    {
        if ($this->predictedTotal == 0) {
            return [
                'tithes' => 0,
                'offerings' => 0,
                'special' => 0,
                'other' => 0,
            ];
        }

        return [
            'tithes' => round(($this->predictedTithes / $this->predictedTotal) * 100, 1),
            'offerings' => round(($this->predictedOfferings / $this->predictedTotal) * 100, 1),
            'special' => round(($this->predictedSpecial / $this->predictedTotal) * 100, 1),
            'other' => round(($this->predictedOther / $this->predictedTotal) * 100, 1),
        ];
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'forecast_type' => $this->forecastType,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'predicted_total' => $this->predictedTotal,
            'predicted_tithes' => $this->predictedTithes,
            'predicted_offerings' => $this->predictedOfferings,
            'predicted_special' => $this->predictedSpecial,
            'predicted_other' => $this->predictedOther,
            'confidence_lower' => $this->confidenceLower,
            'confidence_upper' => $this->confidenceUpper,
            'confidence_score' => $this->confidence,
            'factors' => $this->factors,
            'cohort_breakdown' => $this->cohortBreakdown,
            'budget_target' => $this->budgetTarget,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId: $data['branch_id'],
            forecastType: $data['forecast_type'],
            periodStart: Carbon::parse($data['period_start']),
            periodEnd: Carbon::parse($data['period_end']),
            predictedTotal: (float) $data['predicted_total'],
            predictedTithes: (float) ($data['predicted_tithes'] ?? 0),
            predictedOfferings: (float) ($data['predicted_offerings'] ?? 0),
            predictedSpecial: (float) ($data['predicted_special'] ?? 0),
            predictedOther: (float) ($data['predicted_other'] ?? 0),
            confidenceLower: (float) $data['confidence_lower'],
            confidenceUpper: (float) $data['confidence_upper'],
            confidence: (float) ($data['confidence_score'] ?? $data['confidence'] ?? 0),
            factors: $data['factors'] ?? [],
            cohortBreakdown: $data['cohort_breakdown'] ?? [],
            budgetTarget: isset($data['budget_target']) ? (float) $data['budget_target'] : null,
        );
    }
}
