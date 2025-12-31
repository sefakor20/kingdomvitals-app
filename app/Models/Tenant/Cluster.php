<?php

namespace App\Models\Tenant;

use App\Enums\ClusterType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cluster extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'name',
        'cluster_type',
        'description',
        'leader_id',
        'assistant_leader_id',
        'meeting_day',
        'meeting_time',
        'meeting_location',
        'capacity',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'cluster_type' => ClusterType::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    public function assistantLeader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assistant_leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'cluster_member')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
}
