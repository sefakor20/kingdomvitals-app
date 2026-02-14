<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClusterMeetingAttendance extends Pivot
{
    use HasUuids;

    protected $table = 'cluster_meeting_attendance';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'cluster_meeting_id',
        'member_id',
        'attended',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
        ];
    }

    public function clusterMeeting(): BelongsTo
    {
        return $this->belongsTo(ClusterMeeting::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
