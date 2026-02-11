<?php

namespace App\Models\Tenant;

use App\Enums\HouseholdEngagementLevel;
use Database\Factories\Tenant\HouseholdFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): HouseholdFactory
    {
        return HouseholdFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'head_id',
        'address',
        'engagement_score',
        'engagement_level',
        'attendance_score',
        'giving_score',
        'member_engagement_variance',
        'engagement_factors',
        'engagement_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'engagement_score' => 'decimal:2',
            'engagement_level' => HouseholdEngagementLevel::class,
            'attendance_score' => 'decimal:2',
            'giving_score' => 'decimal:2',
            'member_engagement_variance' => 'decimal:2',
            'engagement_factors' => 'array',
            'engagement_calculated_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class)
            ->whereNotNull('date_of_birth')
            ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18');
    }

    public function adults(): HasMany
    {
        return $this->hasMany(Member::class)
            ->where(function ($query): void {
                $query->whereNull('date_of_birth')
                    ->orWhereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 18');
            });
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Scope to get households by engagement level.
     */
    public function scopeWithEngagementLevel(Builder $query, HouseholdEngagementLevel $level): Builder
    {
        return $query->where('engagement_level', $level->value);
    }

    /**
     * Scope to get households needing outreach.
     */
    public function scopeNeedingOutreach(Builder $query): Builder
    {
        return $query->whereIn('engagement_level', [
            HouseholdEngagementLevel::Low->value,
            HouseholdEngagementLevel::Disengaged->value,
            HouseholdEngagementLevel::PartiallyEngaged->value,
        ]);
    }

    /**
     * Scope to get highly engaged households.
     */
    public function scopeHighlyEngaged(Builder $query): Builder
    {
        return $query->whereIn('engagement_level', [
            HouseholdEngagementLevel::High->value,
            HouseholdEngagementLevel::Medium->value,
        ]);
    }

    /**
     * Check if the household needs outreach.
     */
    public function needsOutreach(): bool
    {
        return $this->engagement_level?->needsOutreach() ?? false;
    }

    /**
     * Check if the household is engaged.
     */
    public function isEngaged(): bool
    {
        return $this->engagement_level?->isEngaged() ?? false;
    }

    /**
     * Check if the household is partially engaged (engagement gap).
     */
    public function hasEngagementGap(): bool
    {
        return $this->engagement_level?->hasEngagementGap() ?? false;
    }
}
