<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'alertable_type',
        'alertable_id',
        'data',
        'recommendations',
        'recommendation_acted_on',
        'recommendation_acted_at',
        'is_read',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'alert_type' => AiAlertType::class,
            'severity' => AlertSeverity::class,
            'data' => 'array',
            'recommendations' => 'array',
            'recommendation_acted_on' => 'boolean',
            'recommendation_acted_at' => 'datetime',
            'is_read' => 'boolean',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeOfType(Builder $query, AiAlertType $type): Builder
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeOfSeverity(Builder $query, AlertSeverity $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter unread alerts.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to filter unacknowledged alerts.
     */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('is_acknowledged', false);
    }

    /**
     * Scope to filter recent alerts (last N days).
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter high priority alerts (high + critical).
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('severity', [AlertSeverity::High, AlertSeverity::Critical]);
    }

    /**
     * Scope to order by severity priority.
     */
    public function scopeOrderBySeverity(Builder $query, string $direction = 'desc'): Builder
    {
        // FIELD returns position (1,2,3,4), so for desc order critical should come first
        // Use ASC with the ordering critical->high->medium->low to get critical first
        $order = $direction === 'desc' ? 'asc' : 'desc';

        return $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low') {$order}");
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update(['is_read' => true]);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(string $userId): bool
    {
        return $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Get the related entity name for display.
     */
    public function getRelatedEntityNameAttribute(): ?string
    {
        $alertable = $this->alertable;

        if (! $alertable) {
            return null;
        }

        return match (true) {
            $alertable instanceof Member => $alertable->fullName(),
            $alertable instanceof Cluster => $alertable->name,
            $alertable instanceof Household => $alertable->name,
            $alertable instanceof PrayerRequest => 'Prayer Request #'.substr($alertable->id, 0, 8),
            default => null,
        };
    }

    /**
     * Get the icon for this alert based on type.
     */
    public function getIconAttribute(): string
    {
        return $this->alert_type->icon();
    }

    /**
     * Get the color for this alert based on severity.
     */
    public function getColorAttribute(): string
    {
        return $this->severity->color();
    }

    /**
     * Check if this alert requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this->severity->requiresImmediateAttention();
    }

    /**
     * Check if a similar alert exists for the same entity within the cooldown period.
     */
    public static function existsForEntity(
        string $branchId,
        AiAlertType $alertType,
        string $alertableType,
        string $alertableId,
        int $cooldownHours = 24
    ): bool {
        return self::where('branch_id', $branchId)
            ->where('alert_type', $alertType)
            ->where('alertable_type', $alertableType)
            ->where('alertable_id', $alertableId)
            ->where('created_at', '>=', now()->subHours($cooldownHours))
            ->exists();
    }

    /**
     * Get recommendations as AlertRecommendation DTOs.
     *
     * @return array<\App\Services\AI\DTOs\AlertRecommendation>
     */
    public function getRecommendationDtosAttribute(): array
    {
        if (empty($this->recommendations)) {
            return [];
        }

        return array_map(
            fn (array $r) => \App\Services\AI\DTOs\AlertRecommendation::fromArray($r),
            $this->recommendations
        );
    }

    /**
     * Check if this alert has recommendations.
     */
    public function hasRecommendations(): bool
    {
        return ! empty($this->recommendations);
    }

    /**
     * Get the count of recommendations.
     */
    public function getRecommendationCountAttribute(): int
    {
        return count($this->recommendations ?? []);
    }

    /**
     * Mark that action has been taken on recommendations.
     */
    public function markRecommendationActedOn(): bool
    {
        return $this->update([
            'recommendation_acted_on' => true,
            'recommendation_acted_at' => now(),
        ]);
    }

    /**
     * Check if recommendations have been acted on.
     */
    public function wasActedOn(): bool
    {
        return $this->recommendation_acted_on === true;
    }
}
