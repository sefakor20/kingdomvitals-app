<?php

namespace App\Models\Tenant;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestPrivacy;
use App\Enums\PrayerRequestStatus;
use App\Enums\PrayerUrgencyLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrayerRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'member_id',
        'cluster_id',
        'title',
        'description',
        'category',
        'status',
        'privacy',
        'submitted_at',
        'answered_at',
        'answer_details',
        'urgency_level',
        'priority_score',
        'ai_classification',
        'ai_analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => PrayerRequestCategory::class,
            'status' => PrayerRequestStatus::class,
            'privacy' => PrayerRequestPrivacy::class,
            'urgency_level' => PrayerUrgencyLevel::class,
            'priority_score' => 'decimal:2',
            'ai_classification' => 'array',
            'submitted_at' => 'datetime',
            'answered_at' => 'datetime',
            'ai_analyzed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(PrayerUpdate::class)->orderBy('created_at', 'desc');
    }

    public function isOpen(): bool
    {
        return $this->status === PrayerRequestStatus::Open;
    }

    public function isAnswered(): bool
    {
        return $this->status === PrayerRequestStatus::Answered;
    }

    public function isCancelled(): bool
    {
        return $this->status === PrayerRequestStatus::Cancelled;
    }

    public function isPublic(): bool
    {
        return $this->privacy === PrayerRequestPrivacy::Public;
    }

    public function isPrivate(): bool
    {
        return $this->privacy === PrayerRequestPrivacy::Private;
    }

    public function isLeadersOnly(): bool
    {
        return $this->privacy === PrayerRequestPrivacy::LeadersOnly;
    }

    public function isAnonymous(): bool
    {
        return $this->member_id === null;
    }

    public function getSubmitterName(): string
    {
        return $this->isAnonymous() ? __('Anonymous') : $this->member->fullName();
    }

    public function markAsAnswered(?string $details = null): void
    {
        $this->update([
            'status' => PrayerRequestStatus::Answered,
            'answered_at' => now(),
            'answer_details' => $details,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => PrayerRequestStatus::Cancelled,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PrayerRequestStatus::Open);
    }

    public function scopeAnswered(Builder $query): Builder
    {
        return $query->where('status', PrayerRequestStatus::Answered);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('privacy', PrayerRequestPrivacy::Public);
    }

    public function scopeForCluster(Builder $query, string $clusterId): Builder
    {
        return $query->where('cluster_id', $clusterId);
    }

    public function scopeVisibleTo(Builder $query, Member $member): Builder
    {
        return $query->where(function ($q) use ($member): void {
            // Public prayers are visible to all
            $q->where('privacy', PrayerRequestPrivacy::Public)
                // Own prayers are always visible
                ->orWhere('member_id', $member->id)
                // Leaders can see cluster prayers
                ->orWhere(function ($q2) use ($member): void {
                    $clusterIds = $member->clusters()->pluck('clusters.id');
                    $q2->whereIn('cluster_id', $clusterIds)
                        ->where('privacy', PrayerRequestPrivacy::LeadersOnly);
                });
        });
    }

    // ============================================
    // AI ANALYSIS METHODS
    // ============================================

    /**
     * Check if this prayer request is urgent (high or critical).
     */
    public function isUrgent(): bool
    {
        return $this->urgency_level?->shouldNotifyPastor() ?? false;
    }

    /**
     * Check if this prayer request needs AI analysis.
     */
    public function needsAnalysis(): bool
    {
        return $this->ai_analyzed_at === null;
    }

    /**
     * Scope to get urgent prayer requests.
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->whereIn('urgency_level', [
            PrayerUrgencyLevel::High->value,
            PrayerUrgencyLevel::Critical->value,
        ]);
    }

    /**
     * Scope to get prayer requests needing AI analysis.
     */
    public function scopeNeedsAnalysis(Builder $query): Builder
    {
        return $query->whereNull('ai_analyzed_at');
    }

    /**
     * Scope to order by priority score descending.
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority_score');
    }

    /**
     * Scope to filter by urgency level.
     */
    public function scopeWithUrgency(Builder $query, PrayerUrgencyLevel $level): Builder
    {
        return $query->where('urgency_level', $level->value);
    }
}
