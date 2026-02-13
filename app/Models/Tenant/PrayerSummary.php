<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerSummary extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'period_type',
        'period_start',
        'period_end',
        'category_breakdown',
        'urgency_breakdown',
        'summary_text',
        'key_themes',
        'pastoral_recommendations',
        'total_requests',
        'answered_requests',
        'critical_requests',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'category_breakdown' => 'array',
            'urgency_breakdown' => 'array',
            'key_themes' => 'array',
            'pastoral_recommendations' => 'array',
            'total_requests' => 'integer',
            'answered_requests' => 'integer',
            'critical_requests' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by period type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('period_type', $type);
    }

    /**
     * Scope to filter weekly summaries.
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    /**
     * Scope to filter monthly summaries.
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    /**
     * Scope to get the latest summary for a branch.
     */
    public function scopeLatestForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId)
            ->orderByDesc('period_end');
    }

    /**
     * Check if this is a weekly summary.
     */
    public function isWeekly(): bool
    {
        return $this->period_type === 'weekly';
    }

    /**
     * Check if this is a monthly summary.
     */
    public function isMonthly(): bool
    {
        return $this->period_type === 'monthly';
    }

    /**
     * Get the answer rate as a percentage.
     */
    public function getAnswerRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return round(($this->answered_requests / $this->total_requests) * 100, 1);
    }

    /**
     * Get formatted period label.
     */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->isWeekly()) {
            return $this->period_start->format('M j').' - '.$this->period_end->format('M j, Y');
        }

        return $this->period_start->format('F Y');
    }

    /**
     * Get the top category from breakdown.
     */
    public function getTopCategoryAttribute(): ?string
    {
        if (empty($this->category_breakdown)) {
            return null;
        }

        $sorted = $this->category_breakdown;
        arsort($sorted);

        return array_key_first($sorted);
    }
}
