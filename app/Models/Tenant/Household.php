<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\HouseholdFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): HouseholdFactory
    {
        return HouseholdFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'head_id',
        'address',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class)
            ->whereNotNull('date_of_birth')
            ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18');
    }

    public function adults(): HasMany
    {
        return $this->hasMany(Member::class)
            ->where(function ($query) {
                $query->whereNull('date_of_birth')
                    ->orWhereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 18');
            });
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }
}
