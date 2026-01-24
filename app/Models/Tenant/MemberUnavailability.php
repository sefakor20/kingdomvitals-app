<?php

namespace App\Models\Tenant;

use App\Models\User;
use Carbon\Carbon;
use Database\Factories\Tenant\MemberUnavailabilityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberUnavailability extends Model
{
    /** @use HasFactory<MemberUnavailabilityFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): MemberUnavailabilityFactory
    {
        return MemberUnavailabilityFactory::new();
    }

    protected $fillable = [
        'member_id',
        'branch_id',
        'unavailable_date',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unavailable_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get unavailabilities for a specific date.
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('unavailable_date', $date);
    }

    /**
     * Scope to get unavailabilities for a specific member.
     */
    public function scopeForMember(Builder $query, string $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to get unavailabilities within a date range.
     */
    public function scopeBetweenDates(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('unavailable_date', [$startDate, $endDate]);
    }

    /**
     * Check if a member is unavailable on a given date for a branch.
     */
    public static function isMemberUnavailable(string $memberId, string $branchId, Carbon $date): bool
    {
        return static::query()
            ->where('member_id', $memberId)
            ->where('branch_id', $branchId)
            ->whereDate('unavailable_date', $date)
            ->exists();
    }
}
