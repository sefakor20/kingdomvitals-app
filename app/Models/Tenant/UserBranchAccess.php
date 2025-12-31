<?php

namespace App\Models\Tenant;

use App\Enums\BranchRole;
use App\Models\User;
use Database\Factories\Tenant\UserBranchAccessFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBranchAccess extends Model
{
    /** @use HasFactory<UserBranchAccessFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): UserBranchAccessFactory
    {
        return UserBranchAccessFactory::new();
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
