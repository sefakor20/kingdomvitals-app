<?php

namespace App\Models\Tenant;

use App\Enums\ClusterRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClusterMember extends Pivot
{
    use HasUuids;

    protected $table = 'cluster_member';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'cluster_id',
        'member_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'role' => ClusterRole::class,
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
