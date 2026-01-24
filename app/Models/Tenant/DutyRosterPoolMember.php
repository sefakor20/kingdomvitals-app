<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DutyRosterPoolMember extends Pivot
{
    use HasUuids;

    protected $table = 'duty_roster_pool_member';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'last_assigned_date' => 'date',
            'assignment_count' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(DutyRosterPool::class, 'duty_roster_pool_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Record an assignment for this pool member.
     */
    public function recordAssignment(\Carbon\Carbon $date): void
    {
        $this->update([
            'last_assigned_date' => $date,
            'assignment_count' => $this->assignment_count + 1,
        ]);
    }

    /**
     * Reset the rotation counters.
     */
    public function resetCounters(): void
    {
        $this->update([
            'last_assigned_date' => null,
            'assignment_count' => 0,
        ]);
    }
}
