<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceForecast extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'service_id',
        'forecast_date',
        'predicted_attendance',
        'predicted_members',
        'predicted_visitors',
        'confidence_score',
        'factors',
        'actual_attendance',
    ];

    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'predicted_attendance' => 'integer',
            'predicted_members' => 'integer',
            'predicted_visitors' => 'integer',
            'confidence_score' => 'decimal:2',
            'factors' => 'array',
            'actual_attendance' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope to get forecasts for a specific branch.
     */
    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get upcoming forecasts.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('forecast_date', '>=', today())
            ->orderBy('forecast_date');
    }

    /**
     * Scope to get past forecasts with actuals recorded.
     */
    public function scopeWithActuals(Builder $query): Builder
    {
        return $query->whereNotNull('actual_attendance');
    }

    /**
     * Scope to get forecasts within a date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('forecast_date', [$startDate, $endDate]);
    }

    /**
     * Calculate the accuracy of this forecast.
     * Returns null if no actual attendance recorded.
     */
    public function getAccuracyAttribute(): ?float
    {
        if ($this->actual_attendance === null || $this->predicted_attendance === 0) {
            return null;
        }

        $error = abs($this->actual_attendance - $this->predicted_attendance);
        $accuracy = 100 - (($error / $this->predicted_attendance) * 100);

        return max(0, min(100, $accuracy));
    }

    /**
     * Get the variance between predicted and actual.
     */
    public function getVarianceAttribute(): ?int
    {
        if ($this->actual_attendance === null) {
            return null;
        }

        return $this->actual_attendance - $this->predicted_attendance;
    }

    /**
     * Get the variance as a percentage.
     */
    public function getVariancePercentAttribute(): ?float
    {
        if ($this->actual_attendance === null || $this->predicted_attendance === 0) {
            return null;
        }

        return (($this->actual_attendance - $this->predicted_attendance) / $this->predicted_attendance) * 100;
    }

    /**
     * Check if the forecast was accurate (within 10%).
     */
    public function wasAccurate(): ?bool
    {
        if ($this->accuracy === null) {
            return null;
        }

        return $this->accuracy >= 90;
    }

    /**
     * Get the confidence level as a string.
     */
    public function confidenceLevel(): string
    {
        return match (true) {
            $this->confidence_score >= 80 => 'high',
            $this->confidence_score >= 50 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get confidence badge color.
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
     * Update with actual attendance and calculate accuracy.
     */
    public function recordActual(int $actualAttendance): bool
    {
        return $this->update([
            'actual_attendance' => $actualAttendance,
        ]);
    }
}
