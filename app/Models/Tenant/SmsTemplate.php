<?php

namespace App\Models\Tenant;

use App\Enums\SmsType;
use Database\Factories\Tenant\SmsTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    /** @use HasFactory<SmsTemplateFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): SmsTemplateFactory
    {
        return SmsTemplateFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'body',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SmsType::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, SmsType $type)
    {
        return $query->where('type', $type);
    }
}
