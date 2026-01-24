<?php

namespace App\Models\Tenant;

use App\Enums\DutyRosterRoleType;
use App\Models\User;
use Database\Factories\Tenant\DutyRosterPoolFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DutyRosterPool extends Model
{
    /** @use HasFactory<DutyRosterPoolFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): DutyRosterPoolFactory
    {
        return DutyRosterPoolFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'role_type',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'role_type' => DutyRosterRoleType::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'duty_roster_pool_member')
            ->using(DutyRosterPoolMember::class)
            ->withPivot(['id', 'last_assigned_date', 'assignment_count', 'sort_order', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get active members from the pool, sorted for round-robin selection.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()
            ->wherePivot('is_active', true)
            ->orderByPivot('assignment_count', 'asc')
            ->orderByPivot('last_assigned_date', 'asc')
            ->orderByPivot('sort_order', 'asc');
    }

    /**
     * Scope to get only active pools.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get pools by role type.
     */
    public function scopeForRole(Builder $query, DutyRosterRoleType $roleType): Builder
    {
        return $query->where('role_type', $roleType);
    }
}
