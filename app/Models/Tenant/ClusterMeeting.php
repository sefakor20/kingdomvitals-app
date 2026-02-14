<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClusterMeeting extends Model
{
    use HasUuids;

    protected $fillable = [
        'cluster_id',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'topic',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(ClusterMeetingAttendance::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'cluster_meeting_attendance')
            ->using(ClusterMeetingAttendance::class)
            ->withPivot(['id', 'attended', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get members who attended this meeting.
     */
    public function presentMembers(): BelongsToMany
    {
        return $this->attendees()->wherePivot('attended', true);
    }

    /**
     * Get members who were absent from this meeting.
     */
    public function absentMembers(): BelongsToMany
    {
        return $this->attendees()->wherePivot('attended', false);
    }

    /**
     * Calculate attendance rate for this meeting.
     */
    public function getAttendanceRateAttribute(): float
    {
        $total = $this->attendanceRecords()->count();
        if ($total === 0) {
            return 0.0;
        }

        $present = $this->attendanceRecords()->where('attended', true)->count();

        return round(($present / $total) * 100, 2);
    }

    /**
     * Scope for meetings within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('meeting_date', [$startDate, $endDate]);
    }

    /**
     * Scope for meetings in the last N days.
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('meeting_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for meetings of a specific cluster.
     */
    public function scopeForCluster($query, string $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
    }
}
