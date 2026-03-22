<?php

namespace App\Models\Tenant;

use App\Enums\EmailType;
use Database\Factories\Tenant\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'subject',
        'body',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmailType::class,
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

    public function scopeOfType($query, EmailType $type)
    {
        return $query->where('type', $type);
    }
}
