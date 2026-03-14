<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\RiskLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PledgePrediction extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'pledge_id',
        'member_id',
        'fulfillment_probability',
        'risk_level',
        'recommended_nudge_at',
        'factors',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'fulfillment_probability' => 'decimal:2',
            'risk_level' => RiskLevel::class,
            'recommended_nudge_at' => 'datetime',
            'factors' => 'array',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<PledgePrediction>  $query
     * @return Builder<PledgePrediction>
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('risk_level', RiskLevel::High);
    }

    /**
     * @param  Builder<PledgePrediction>  $query
     * @return Builder<PledgePrediction>
     */
    public function scopeAtRisk(Builder $query): Builder
    {
        return $query->whereIn('risk_level', [RiskLevel::High, RiskLevel::Medium]);
    }

    /**
     * @param  Builder<PledgePrediction>  $query
     * @return Builder<PledgePrediction>
     */
    public function scopeNudgeDue(Builder $query): Builder
    {
        return $query->whereNotNull('recommended_nudge_at')
            ->where('recommended_nudge_at', '<=', now());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if the prediction suggests sending a nudge.
     */
    public function shouldSendNudge(): bool
    {
        if (! $this->risk_level->shouldSendNudge()) {
            return false;
        }

        if ($this->recommended_nudge_at === null) {
            return true;
        }

        return $this->recommended_nudge_at <= now();
    }

    /**
     * Get a human-readable summary of the prediction.
     */
    public function getSummary(): string
    {
        return sprintf(
            '%s has a %s%% chance of fulfilling their pledge of %s',
            $this->member->fullName(),
            number_format($this->fulfillment_probability, 0),
            $this->pledge->formatted_amount ?? 'unknown amount'
        );
    }
}
