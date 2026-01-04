<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\CheckoutStatus;
use App\Enums\EquipmentCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentCheckout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'equipment_id',
        'branch_id',
        'member_id',
        'checked_out_by',
        'approved_by',
        'checked_in_by',
        'status',
        'checkout_date',
        'expected_return_date',
        'actual_return_date',
        'return_condition',
        'purpose',
        'checkout_notes',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'checkout_date' => 'datetime',
            'expected_return_date' => 'datetime',
            'actual_return_date' => 'datetime',
            'status' => CheckoutStatus::class,
            'return_condition' => EquipmentCondition::class,
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === CheckoutStatus::Approved
            && $this->expected_return_date->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === CheckoutStatus::Pending;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [CheckoutStatus::Pending, CheckoutStatus::Approved]);
    }
}
