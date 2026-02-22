<?php

namespace App\Models\Tenant;

use App\Enums\ServiceType;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
use App\Observers\ServiceObserver;
use Database\Factories\Tenant\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ServiceObserver::class])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasActivityLogging, HasFactory, HasUuids;

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'name',
        'day_of_week',
        'time',
        'service_type',
        'capacity',
        'is_active',
        'forecast_next_attendance',
        'forecast_confidence',
        'forecast_factors',
        'forecast_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'service_type' => ServiceType::class,
            'forecast_next_attendance' => 'decimal:2',
            'forecast_confidence' => 'decimal:2',
            'forecast_factors' => 'array',
            'forecast_calculated_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Service;
    }

    public function getActivitySubjectName(): string
    {
        return $this->name;
    }

    public function getActivityBranchId(): string
    {
        return $this->branch_id;
    }
}
