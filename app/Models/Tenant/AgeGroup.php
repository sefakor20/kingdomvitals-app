<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\AgeGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgeGroup extends Model
{
    /** @use HasFactory<AgeGroupFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): AgeGroupFactory
    {
        return AgeGroupFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'min_age',
        'max_age',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class, 'age_group_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('min_age');
    }

    public function ageRange(): string
    {
        return "{$this->min_age}-{$this->max_age} yrs";
    }
}
