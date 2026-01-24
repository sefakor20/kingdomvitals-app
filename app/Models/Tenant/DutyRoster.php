<?php

namespace App\Models\Tenant;

use App\Enums\DutyRosterStatus;
use App\Models\User;
use Database\Factories\Tenant\DutyRosterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class DutyRoster extends Model
{
    /** @use HasFactory<DutyRosterFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): DutyRosterFactory
    {
        return DutyRosterFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'service_id',
        'service_date',
        'theme',
        'preacher_id',
        'preacher_name',
        'liturgist_id',
        'liturgist_name',
        'hymn_numbers',
        'remarks',
        'status',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'hymn_numbers' => 'array',
            'status' => DutyRosterStatus::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
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

    public function preacher(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'preacher_id');
    }

    public function liturgist(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'liturgist_id');
    }

    public function scriptures(): HasMany
    {
        return $this->hasMany(DutyRosterScripture::class)->orderBy('sort_order');
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'duty_roster_cluster')
            ->using(DutyRosterCluster::class)
            ->withPivot(['id', 'notes'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all readers assigned to scriptures for this roster.
     *
     * @return Collection<int, Member>
     */
    public function readers(): Collection
    {
        return $this->scriptures()
            ->whereNotNull('reader_id')
            ->with('reader')
            ->get()
            ->pluck('reader')
            ->filter()
            ->unique('id');
    }

    /**
     * Get display name for preacher (member name or external name).
     */
    public function getPreacherDisplayNameAttribute(): ?string
    {
        if ($this->preacher) {
            return $this->preacher->fullName();
        }

        return $this->preacher_name;
    }

    /**
     * Get display name for liturgist (member name or external name).
     */
    public function getLiturgistDisplayNameAttribute(): ?string
    {
        if ($this->liturgist) {
            return $this->liturgist->fullName();
        }

        return $this->liturgist_name;
    }

    /**
     * Get formatted hymn numbers as comma-separated string.
     */
    public function getHymnNumbersDisplayAttribute(): string
    {
        return $this->hymn_numbers ? implode(', ', $this->hymn_numbers) : '';
    }

    /**
     * Publish the roster.
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'status' => DutyRosterStatus::Published,
        ]);
    }

    /**
     * Unpublish the roster.
     */
    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
            'status' => DutyRosterStatus::Draft,
        ]);
    }

    /**
     * Mark the roster as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => DutyRosterStatus::Completed,
        ]);
    }

    /**
     * Cancel the roster.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => DutyRosterStatus::Cancelled,
        ]);
    }
}
