<?php

namespace App\Models\Tenant;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'equipment';

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'description',
        'serial_number',
        'model_number',
        'manufacturer',
        'purchase_date',
        'purchase_price',
        'currency',
        'condition',
        'location',
        'assigned_to',
        'warranty_expiry',
        'last_maintenance_date',
        'next_maintenance_date',
        'photo_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'warranty_expiry' => 'date',
            'last_maintenance_date' => 'date',
            'next_maintenance_date' => 'date',
            'category' => EquipmentCategory::class,
            'condition' => EquipmentCondition::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_to');
    }
}
