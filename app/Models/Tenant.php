<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'status',
        'trial_ends_at',
        'suspended_at',
        'suspension_reason',
        'subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'status' => TenantStatus::class,
        ];
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'contact_email',
            'contact_phone',
            'address',
            'status',
            'trial_ends_at',
            'suspended_at',
            'suspension_reason',
            'subscription_id',
        ];
    }

    /**
     * Get the subscription plan for this tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_id');
    }

    /**
     * Check if tenant is in trial period
     */
    public function isInTrial(): bool
    {
        return $this->status === TenantStatus::Trial &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Trial || $this->status === TenantStatus::Active;
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    /**
     * Suspend the tenant with a reason.
     */
    public function suspend(string $reason): void
    {
        $this->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Reactivate a suspended or inactive tenant.
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Get days remaining in trial.
     */
    public function trialDaysRemaining(): ?int
    {
        if (! $this->isInTrial() || ! $this->trial_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }
}
