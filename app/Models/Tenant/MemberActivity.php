<?php

namespace App\Models\Tenant;

use App\Enums\ActivityEvent;
use App\Models\User;
use Database\Factories\Tenant\MemberActivityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberActivity extends Model
{
    /** @use HasFactory<MemberActivityFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): MemberActivityFactory
    {
        return MemberActivityFactory::new();
    }

    protected $fillable = [
        'member_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_fields' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedDescriptionAttribute(): string
    {
        $userName = $this->user?->name ?? 'System';

        return match ($this->event) {
            ActivityEvent::Created => "{$userName} created this member",
            ActivityEvent::Updated => "{$userName} updated ".$this->formatChangedFields(),
            ActivityEvent::Deleted => "{$userName} deleted this member",
            ActivityEvent::Restored => "{$userName} restored this member",
            default => "{$userName} performed an action",
        };
    }

    protected function formatChangedFields(): string
    {
        $fields = $this->changed_fields ?? [];
        $count = count($fields);

        if ($count === 0) {
            return 'member details';
        }

        if ($count <= 3) {
            return implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), $fields));
        }

        return implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), array_slice($fields, 0, 2)))
            .' and '.($count - 2).' more fields';
    }
}
