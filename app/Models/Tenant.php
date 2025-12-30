<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids;

    protected $fillable = [
        'name',
        'status',
        'trial_ends_at',
        'subscription_id',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'trial_ends_at',
            'subscription_id',
        ];
    }

    /**
     * Check if tenant is in trial period
     */
    public function isInTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']);
    }
}
