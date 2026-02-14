<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialForecast extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'forecast_type',
        'period_start',
        'period_end',
        'predicted_total',
        'predicted_tithes',
        'predicted_offerings',
        'predicted_special',
        'predicted_other',
        'confidence_lower',
        'confidence_upper',
        'confidence_score',
        'factors',
        'cohort_breakdown',
        'actual_total',
        'budget_target',
        'gap_amount',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'predicted_total' => 'decimal:2',
            'predicted_tithes' => 'decimal:2',
            'predicted_offerings' => 'decimal:2',
            'predicted_special' => 'decimal:2',
            'predicted_other' => 'decimal:2',
            'confidence_lower' => 'decimal:2',
            'confidence_upper' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'factors' => 'array',
            'cohort_breakdown' => 'array',
            'actual_total' => 'decimal:2',
            'budget_target' => 'decimal:2',
            'gap_amount' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter weekly forecasts.
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('forecast_type', 'weekly');
    }

    /**
     * Scope to filter monthly forecasts.
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('forecast_type', 'monthly');
    }

    /**
     * Scope to filter quarterly forecasts.
     */
    public function scopeQuarterly(Builder $query): Builder
    {
        return $query->where('forecast_type', 'quarterly');
    }

    /**
     * Scope to get upcoming forecasts (period starts in the future).
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('period_start', '>=', now()->startOfDay())
            ->orderBy('period_start');
    }

    /**
     * Scope to get forecasts with recorded actuals.
     */
    public function scopeWithActuals(Builder $query): Builder
    {
        return $query->whereNotNull('actual_total');
    }

    /**
     * Check if this is a weekly forecast.
     */
    public function isWeekly(): bool
    {
        return $this->forecast_type === 'weekly';
    }

    /**
     * Check if this is a monthly forecast.
     */
    public function isMonthly(): bool
    {
        return $this->forecast_type === 'monthly';
    }

    /**
     * Check if this is a quarterly forecast.
     */
    public function isQuarterly(): bool
    {
        return $this->forecast_type === 'quarterly';
    }

    /**
     * Get accuracy as percentage (how close prediction was to actual).
     */
    public function getAccuracyAttribute(): ?float
    {
        if ($this->actual_total === null || $this->predicted_total == 0) {
            return null;
        }

        $error = abs($this->actual_total - $this->predicted_total);
        $accuracy = max(0, 100 - (($error / $this->predicted_total) * 100));

        return round($accuracy, 1);
    }

    /**
     * Get variance amount (actual - predicted).
     */
    public function getVarianceAttribute(): ?float
    {
        if ($this->actual_total === null) {
            return null;
        }

        return (float) $this->actual_total - (float) $this->predicted_total;
    }

    /**
     * Get variance as percentage.
     */
    public function getVariancePercentAttribute(): ?float
    {
        if ($this->actual_total === null || $this->predicted_total == 0) {
            return null;
        }

        return round((($this->actual_total - $this->predicted_total) / $this->predicted_total) * 100, 1);
    }

    /**
     * Get gap percentage vs budget.
     */
    public function getGapPercentageAttribute(): ?float
    {
        if ($this->budget_target === null || $this->budget_target == 0) {
            return null;
        }

        return round((($this->predicted_total - $this->budget_target) / $this->budget_target) * 100, 1);
    }

    /**
     * Check if forecast was accurate (within 10% of actual).
     */
    public function wasAccurate(): ?bool
    {
        if ($this->accuracy === null) {
            return null;
        }

        return $this->accuracy >= 90;
    }

    /**
     * Check if predicted giving meets or exceeds budget target.
     */
    public function isOnTrack(): ?bool
    {
        if ($this->budget_target === null) {
            return null;
        }

        return $this->predicted_total >= $this->budget_target;
    }

    /**
     * Get confidence level as string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidence_score >= 80 => 'high',
            $this->confidence_score >= 60 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get badge color for confidence level.
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
     * Get formatted period label.
     */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->isWeekly()) {
            return $this->period_start->format('M j').' - '.$this->period_end->format('M j, Y');
        }

        if ($this->isMonthly()) {
            return $this->period_start->format('F Y');
        }

        // Quarterly
        $quarter = ceil($this->period_start->month / 3);

        return 'Q'.$quarter.' '.$this->period_start->format('Y');
    }

    /**
     * Record actual total and calculate gap.
     */
    public function recordActual(float $actualTotal): bool
    {
        return $this->update([
            'actual_total' => $actualTotal,
            'gap_amount' => $this->budget_target !== null
                ? $actualTotal - $this->budget_target
                : null,
        ]);
    }
}
