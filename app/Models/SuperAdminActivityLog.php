<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminActivityLog extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $table = 'super_admin_activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'super_admin_id',
        'tenant_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the super admin that performed this action.
     */
    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    /**
     * Get the tenant associated with this action (if any).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Create a new activity log entry.
     */
    public static function log(
        SuperAdmin $superAdmin,
        string $action,
        ?string $description = null,
        ?Tenant $tenant = null,
        array $metadata = []
    ): self {
        return static::create([
            'super_admin_id' => $superAdmin->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
