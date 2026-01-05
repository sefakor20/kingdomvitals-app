<?php

namespace App\Models\Tenant;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'donation_id',
        'branch_id',
        'paystack_reference',
        'paystack_transaction_id',
        'amount',
        'currency',
        'status',
        'channel',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentTransactionStatus::class,
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentTransactionStatus::Pending;
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentTransactionStatus::Success;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentTransactionStatus::Failed;
    }

    public function markAsSuccessful(string $transactionId, ?string $channel = null): void
    {
        $this->update([
            'status' => PaymentTransactionStatus::Success,
            'paystack_transaction_id' => $transactionId,
            'channel' => $channel,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => PaymentTransactionStatus::Failed,
        ]);
    }
}
