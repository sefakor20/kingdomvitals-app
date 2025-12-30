<?php

namespace App\Models\Tenant;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'member_id',
        'phone_number',
        'message',
        'message_type',
        'status',
        'provider',
        'provider_message_id',
        'cost',
        'currency',
        'sent_at',
        'delivered_at',
        'error_message',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'message_type' => SmsType::class,
            'status' => SmsStatus::class,
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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sent_by');
    }
}
