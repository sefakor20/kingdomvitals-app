<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\ChildEmergencyContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildEmergencyContact extends Model
{
    /** @use HasFactory<ChildEmergencyContactFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): ChildEmergencyContactFactory
    {
        return ChildEmergencyContactFactory::new();
    }

    protected $fillable = [
        'member_id',
        'name',
        'relationship',
        'phone',
        'phone_secondary',
        'email',
        'is_primary',
        'can_pickup',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
