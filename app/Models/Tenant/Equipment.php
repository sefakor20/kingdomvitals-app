<?php

namespace App\Models\Tenant;

use App\Enums\CheckoutStatus;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function checkouts(): HasMany
    {
        return $this->hasMany(EquipmentCheckout::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }

    public function activeCheckout(): HasOne
    {
        return $this->hasOne(EquipmentCheckout::class)
            ->whereIn('status', [CheckoutStatus::Pending->value, CheckoutStatus::Approved->value])
            ->latest();
    }

    public function isAvailable(): bool
    {
        return $this->condition !== EquipmentCondition::OutOfService
            && ! $this->activeCheckout()->exists();
    }

    public function isCheckedOut(): bool
    {
        return $this->activeCheckout()->exists();
    }

    public function isOutOfService(): bool
    {
        return $this->condition === EquipmentCondition::OutOfService;
    }

    public function maintenanceDue(): bool
    {
        return $this->next_maintenance_date !== null
            && $this->next_maintenance_date->isPast();
    }

    public function warrantyExpired(): bool
    {
        return $this->warranty_expiry !== null
            && $this->warranty_expiry->isPast();
    }
}
