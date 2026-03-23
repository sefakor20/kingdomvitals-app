<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class MemberInvitation extends Model
{
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'branch_id',
        'member_id',
        'email',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
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

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    public function scopeForMember(Builder $query, string $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function markAsAccepted(): void
    {
        $this->update(['accepted_at' => now()]);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Create a new invitation for a member.
     */
    public static function createForMember(Member $member, ?User $invitedBy = null): self
    {
        // Invalidate any existing pending invitations
        static::forMember($member->id)->pending()->delete();

        return static::create([
            'branch_id' => $member->primary_branch_id,
            'member_id' => $member->id,
            'email' => $member->email,
            'token' => static::generateToken(),
            'invited_by' => $invitedBy?->id,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
