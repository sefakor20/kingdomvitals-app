<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DutyRosterCluster extends Pivot
{
    use HasUuids;

    protected $table = 'duty_roster_cluster';

    public $incrementing = false;

    protected $keyType = 'string';
}
