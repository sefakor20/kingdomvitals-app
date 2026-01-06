<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SuperAdminRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class SuperAdmin extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'super_admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => SuperAdminRole::class,
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Check if account is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Check if the admin has full access (owner or admin role).
     */
    public function hasFullAccess(): bool
    {
        return $this->role->hasFullAccess();
    }

    /**
     * Check if the admin is the owner.
     */
    public function isOwner(): bool
    {
        return $this->role === SuperAdminRole::Owner;
    }

    /**
     * Record a successful login.
     */
    public function recordLogin(?string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailedLogin(int $maxAttempts = 3, int $lockoutMinutes = 15): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= $maxAttempts) {
            $this->update([
                'locked_until' => now()->addMinutes($lockoutMinutes),
            ]);
        }
    }

    /**
     * Scope to only active admins.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeWithRole(Builder $query, SuperAdminRole $role): Builder
    {
        return $query->where('role', $role);
    }
}
