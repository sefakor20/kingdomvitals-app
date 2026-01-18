<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\BranchRole;
use App\Models\User;
use Database\Factories\Tenant\BranchUserInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class BranchUserInvitation extends Model
{
    /** @use HasFactory<BranchUserInvitationFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    protected static function newFactory(): BranchUserInvitationFactory
    {
        return BranchUserInvitationFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => BranchRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
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
}
