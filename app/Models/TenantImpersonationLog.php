<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantImpersonationLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'super_admin_id',
        'tenant_id',
        'reason',
        'started_at',
        'ended_at',
        'duration_minutes',
        'ip_address',
        'user_agent',
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SuperAdmin, $this>
     */
    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function startSession(
        SuperAdmin $superAdmin,
        Tenant $tenant,
        string $reason
    ): self {
        return static::create([
            'super_admin_id' => $superAdmin->id,
            'tenant_id' => $tenant->id,
            'reason' => $reason,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'token' => bin2hex(random_bytes(32)),
        ]);
    }

    public function endSession(): void
    {
        $this->update([
            'ended_at' => now(),
            'duration_minutes' => now()->diffInMinutes($this->started_at),
        ]);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function isExpired(int $maxMinutes = 60): bool
    {
        return $this->started_at->addMinutes($maxMinutes)->isPast();
    }
}
