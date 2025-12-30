<?php

namespace App\Models\Tenant;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'member_id',
        'service_id',
        'amount',
        'currency',
        'donation_type',
        'payment_method',
        'donation_date',
        'reference_number',
        'donor_name',
        'notes',
        'is_anonymous',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'donation_date' => 'date',
            'is_anonymous' => 'boolean',
            'donation_type' => DonationType::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'recorded_by');
    }
}
