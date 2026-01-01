<?php

namespace App\Models\Tenant;

use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Models\User;
use Database\Factories\Tenant\VisitorFollowUpFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorFollowUp extends Model
{
    /** @use HasFactory<VisitorFollowUpFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): VisitorFollowUpFactory
    {
        return VisitorFollowUpFactory::new();
    }

    protected $fillable = [
        'visitor_id',
        'performed_by',
        'created_by_user_id',
        'type',
        'outcome',
        'notes',
        'scheduled_at',
        'completed_at',
        'is_scheduled',
        'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'outcome' => FollowUpOutcome::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_scheduled' => 'boolean',
            'reminder_sent' => 'boolean',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'performed_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->outcome === FollowUpOutcome::Pending;
    }

    public function isOverdue(): bool
    {
        return $this->is_scheduled
            && $this->scheduled_at
            && $this->scheduled_at->isPast()
            && $this->outcome === FollowUpOutcome::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->outcome !== FollowUpOutcome::Pending && $this->completed_at !== null;
    }
}
