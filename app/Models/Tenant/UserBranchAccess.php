<?php

namespace App\Models\Tenant;

use App\Enums\BranchRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBranchAccess extends Model
{
    use HasUuids;

    protected $table = 'user_branch_access';

    protected $fillable = [
        'user_id',
        'branch_id',
        'role',
        'is_primary',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'permissions' => 'array',
            'role' => BranchRole::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
