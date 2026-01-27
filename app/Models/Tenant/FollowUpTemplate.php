<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\FollowUpType;
use Database\Factories\Tenant\FollowUpTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpTemplate extends Model
{
    /** @use HasFactory<FollowUpTemplateFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): FollowUpTemplateFactory
    {
        return FollowUpTemplateFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'body',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<FollowUpTemplate>  $query
     * @return Builder<FollowUpTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<FollowUpTemplate>  $query
     * @return Builder<FollowUpTemplate>
     */
    public function scopeOfType(Builder $query, ?FollowUpType $type): Builder
    {
        if ($type === null) {
            return $query->whereNull('type');
        }

        return $query->where('type', $type);
    }

    /**
     * @param  Builder<FollowUpTemplate>  $query
     * @return Builder<FollowUpTemplate>
     */
    public function scopeForTypeOrGeneric(Builder $query, ?FollowUpType $type): Builder
    {
        return $query->where(function ($q) use ($type) {
            $q->whereNull('type');
            if ($type !== null) {
                $q->orWhere('type', $type);
            }
        });
    }
}
