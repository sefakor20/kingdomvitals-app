<?php

namespace App\Models\Tenant;

use App\Enums\ActivityEvent;
use App\Enums\SubjectType;
use App\Models\User;
use Database\Factories\Tenant\ActivityLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): ActivityLogFactory
    {
        return ActivityLogFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'user_id',
        'subject_type',
        'subject_id',
        'subject_name',
        'event',
        'description',
        'old_values',
        'new_values',
        'changed_fields',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'subject_type' => SubjectType::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_fields' => 'array',
            'metadata' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $userName = $this->user?->name ?? 'System';
        $subjectName = $this->subject_name ?? $this->subject_type->label();

        return match ($this->event) {
            ActivityEvent::Created => "{$userName} created {$subjectName}",
            ActivityEvent::Updated => "{$userName} updated {$subjectName}",
            ActivityEvent::Deleted => "{$userName} deleted {$subjectName}",
            ActivityEvent::Restored => "{$userName} restored {$subjectName}",
            ActivityEvent::Login => "{$userName} logged in",
            ActivityEvent::Logout => "{$userName} logged out",
            ActivityEvent::FailedLogin => "Failed login attempt for {$subjectName}",
            ActivityEvent::Exported => "{$userName} exported {$subjectName}",
            ActivityEvent::Imported => "{$userName} imported {$subjectName}",
            ActivityEvent::BulkUpdated => "{$userName} bulk updated {$subjectName}",
            ActivityEvent::BulkDeleted => "{$userName} bulk deleted {$subjectName}",
            default => "{$userName} performed an action on {$subjectName}",
        };
    }

    /**
     * Create an activity log entry with full context.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string>|null  $changedFields
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        string $branchId,
        ActivityEvent $event,
        SubjectType $subjectType,
        ?string $subjectId = null,
        ?string $subjectName = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $changedFields = null,
        array $metadata = []
    ): self {
        return static::create([
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'event' => $event,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
