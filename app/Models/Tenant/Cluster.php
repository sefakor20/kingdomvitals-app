<?php

namespace App\Models\Tenant;

use App\Enums\ClusterHealthLevel;
use App\Enums\ClusterType;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
use App\Observers\ClusterObserver;
use Database\Factories\Tenant\ClusterFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ClusterObserver::class])]
class Cluster extends Model
{
    /** @use HasFactory<ClusterFactory> */
    use HasActivityLogging, HasFactory, HasUuids;

    protected static function newFactory(): ClusterFactory
    {
        return ClusterFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'cluster_type',
        'description',
        'leader_id',
        'assistant_leader_id',
        'meeting_day',
        'meeting_time',
        'meeting_location',
        'capacity',
        'is_active',
        'notes',
        'health_score',
        'health_level',
        'health_factors',
        'health_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'cluster_type' => ClusterType::class,
            'health_score' => 'decimal:2',
            'health_level' => ClusterHealthLevel::class,
            'health_factors' => 'array',
            'health_calculated_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    public function assistantLeader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assistant_leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'cluster_member')
            ->using(ClusterMember::class)
            ->withPivot(['id', 'role', 'joined_at'])
            ->withTimestamps();
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ClusterMeeting::class);
    }

    /**
     * Scope to get clusters by health level.
     */
    public function scopeWithHealthLevel(Builder $query, ClusterHealthLevel $level): Builder
    {
        return $query->where('health_level', $level->value);
    }

    /**
     * Scope to get clusters needing attention.
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('health_level', [
            ClusterHealthLevel::Struggling->value,
            ClusterHealthLevel::Critical->value,
        ]);
    }

    /**
     * Scope to get healthy clusters.
     */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->whereIn('health_level', [
            ClusterHealthLevel::Thriving->value,
            ClusterHealthLevel::Healthy->value,
        ]);
    }

    /**
     * Scope to get only active clusters.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the cluster needs attention.
     */
    public function needsAttention(): bool
    {
        return $this->health_level?->needsAttention() ?? false;
    }

    /**
     * Check if the cluster is performing well.
     */
    public function isPerformingWell(): bool
    {
        return $this->health_level?->isPerformingWell() ?? false;
    }

    /**
     * Check if the cluster is thriving.
     */
    public function isThriving(): bool
    {
        return $this->health_level === ClusterHealthLevel::Thriving;
    }

    /**
     * Get recommended check-in frequency in days.
     */
    public function checkInFrequencyDays(): int
    {
        return $this->health_level?->checkInFrequencyDays() ?? 30;
    }

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Cluster;
    }

    public function getActivitySubjectName(): string
    {
        return $this->name;
    }

    public function getActivityBranchId(): string
    {
        return $this->branch_id;
    }
}
