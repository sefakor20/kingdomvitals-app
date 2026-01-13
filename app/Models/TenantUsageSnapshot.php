<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUsageSnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'total_members',
        'active_members',
        'total_branches',
        'sms_sent_this_month',
        'sms_cost_this_month',
        'donations_this_month',
        'donation_count_this_month',
        'attendance_this_month',
        'visitors_this_month',
        'visitor_conversions_this_month',
        'active_modules',
        'member_quota_usage_percent',
        'branch_quota_usage_percent',
        'sms_quota_usage_percent',
        'storage_quota_usage_percent',
        'snapshot_date',
    ];

    protected function casts(): array
    {
        return [
            'active_modules' => 'array',
            'snapshot_date' => 'date',
            'sms_cost_this_month' => 'decimal:2',
            'donations_this_month' => 'decimal:2',
            'member_quota_usage_percent' => 'decimal:2',
            'branch_quota_usage_percent' => 'decimal:2',
            'sms_quota_usage_percent' => 'decimal:2',
            'storage_quota_usage_percent' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->where('snapshot_date', now()->toDateString());
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('snapshot_date', $date);
    }

    public function scopeApproachingLimits(Builder $query, float $threshold = 80): Builder
    {
        return $query->where(function (Builder $q) use ($threshold): void {
            $q->where('member_quota_usage_percent', '>=', $threshold)
                ->orWhere('branch_quota_usage_percent', '>=', $threshold)
                ->orWhere('sms_quota_usage_percent', '>=', $threshold)
                ->orWhere('storage_quota_usage_percent', '>=', $threshold);
        });
    }

    public function scopeWithActiveTenants(Builder $query): Builder
    {
        return $query->whereHas('tenant', function (Builder $q): void {
            $q->whereIn('status', ['active', 'trial']);
        });
    }

    /**
     * Get all quota alerts for this snapshot.
     *
     * @return array<int, array{type: string, usage: float}>
     */
    public function getQuotaAlerts(float $threshold = 80): array
    {
        $alerts = [];

        if ($this->member_quota_usage_percent !== null && $this->member_quota_usage_percent >= $threshold) {
            $alerts[] = ['type' => 'members', 'usage' => (float) $this->member_quota_usage_percent];
        }

        if ($this->branch_quota_usage_percent !== null && $this->branch_quota_usage_percent >= $threshold) {
            $alerts[] = ['type' => 'branches', 'usage' => (float) $this->branch_quota_usage_percent];
        }

        if ($this->sms_quota_usage_percent !== null && $this->sms_quota_usage_percent >= $threshold) {
            $alerts[] = ['type' => 'sms', 'usage' => (float) $this->sms_quota_usage_percent];
        }

        if ($this->storage_quota_usage_percent !== null && $this->storage_quota_usage_percent >= $threshold) {
            $alerts[] = ['type' => 'storage', 'usage' => (float) $this->storage_quota_usage_percent];
        }

        return $alerts;
    }
}
