<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialSignup extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'email',
        'tenant_id',
        'ip_address',
        'user_agent',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
