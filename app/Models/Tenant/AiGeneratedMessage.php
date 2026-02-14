<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\AiMessageStatus;
use App\Enums\FollowUpType;
use App\Models\User;
use Database\Factories\Tenant\AiGeneratedMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneratedMessage extends Model
{
    /** @use HasFactory<AiGeneratedMessageFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): AiGeneratedMessageFactory
    {
        return AiGeneratedMessageFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'visitor_id',
        'member_id',
        'message_type',
        'channel',
        'generated_content',
        'context_used',
        'status',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'approved_by',
        'approved_at',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'context_used' => 'array',
            'tokens_used' => 'integer',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'status' => AiMessageStatus::class,
            'channel' => FollowUpType::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the recipient (visitor or member).
     */
    public function getRecipientAttribute(): Visitor|Member|null
    {
        return $this->visitor ?? $this->member;
    }

    /**
     * Get the recipient's name.
     */
    public function getRecipientNameAttribute(): ?string
    {
        return $this->recipient?->fullName();
    }

    /**
     * Check if the message is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === AiMessageStatus::Pending;
    }

    /**
     * Check if the message has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === AiMessageStatus::Approved;
    }

    /**
     * Check if the message has been sent.
     */
    public function isSent(): bool
    {
        return $this->status === AiMessageStatus::Sent;
    }

    /**
     * Approve the message.
     */
    public function approve(User $user): bool
    {
        return $this->update([
            'status' => AiMessageStatus::Approved,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the message.
     */
    public function reject(): bool
    {
        return $this->update([
            'status' => AiMessageStatus::Rejected,
        ]);
    }

    /**
     * Mark the message as sent.
     */
    public function markAsSent(User $user): bool
    {
        return $this->update([
            'status' => AiMessageStatus::Sent,
            'sent_by' => $user->id,
            'sent_at' => now(),
        ]);
    }

    /**
     * Get character count.
     */
    public function getCharacterCountAttribute(): int
    {
        return mb_strlen($this->generated_content ?? '');
    }
}
