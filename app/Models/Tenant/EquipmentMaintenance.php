<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\EquipmentCondition;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'equipment_maintenance';

    protected $fillable = [
        'equipment_id',
        'branch_id',
        'requested_by',
        'performed_by',
        'type',
        'status',
        'scheduled_date',
        'completed_date',
        'description',
        'findings',
        'work_performed',
        'service_provider',
        'cost',
        'currency',
        'condition_before',
        'condition_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'cost' => 'decimal:2',
            'type' => MaintenanceType::class,
            'status' => MaintenanceStatus::class,
            'condition_before' => EquipmentCondition::class,
            'condition_after' => EquipmentCondition::class,
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isScheduled(): bool
    {
        return $this->status === MaintenanceStatus::Scheduled;
    }

    public function isCompleted(): bool
    {
        return $this->status === MaintenanceStatus::Completed;
    }

    public function isDue(): bool
    {
        return $this->status === MaintenanceStatus::Scheduled
            && $this->scheduled_date->isPast();
    }
}
