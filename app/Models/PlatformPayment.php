<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPayment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'platform_invoice_id',
        'tenant_id',
        'payment_reference',
        'paystack_reference',
        'amount',
        'currency',
        'payment_method',
        'status',
        'notes',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PlatformPaymentMethod::class,
            'status' => PlatformPaymentStatus::class,
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PlatformPayment $payment): void {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }
        });
    }

    public static function generatePaymentReference(): string
    {
        $prefix = 'PAY';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', PlatformPaymentStatus::Successful);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PlatformPaymentStatus::Pending);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PlatformPaymentStatus::Failed);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    public function markAsSuccessful(): void
    {
        $this->update([
            'status' => PlatformPaymentStatus::Successful,
            'paid_at' => now(),
        ]);

        $this->invoice->recordPayment((float) $this->amount);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => PlatformPaymentStatus::Failed]);
    }

    public function refund(): void
    {
        $this->update(['status' => PlatformPaymentStatus::Refunded]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PlatformPaymentStatus::Successful;
    }
}
