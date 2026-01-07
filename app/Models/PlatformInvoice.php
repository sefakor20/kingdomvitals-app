<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformInvoice extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'tenant_id',
        'subscription_plan_id',
        'billing_period',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'currency',
        'notes',
        'metadata',
        'sent_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PlatformInvoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        $lastInvoice = self::withTrashed()
            ->where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('INV-%s%s-%04d', $year, $month, $newNumber);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PlatformPaymentReminder::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Draft);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Sent);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Paid);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Overdue);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::Sent,
            InvoiceStatus::Partial,
            InvoiceStatus::Overdue,
        ]);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public function calculateBalance(): float
    {
        return (float) $this->total_amount - (float) $this->amount_paid;
    }

    public function recalculateFromItems(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total_amount = (float) $this->subtotal - (float) $this->discount_amount + (float) $this->tax_amount;
        $this->balance_due = $this->calculateBalance();
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'balance_due' => 0,
        ]);
    }

    public function markAsOverdue(): void
    {
        if ($this->status->canReceivePayment() && $this->due_date->isPast()) {
            $this->update(['status' => InvoiceStatus::Overdue]);
        }
    }

    public function markAsPartial(): void
    {
        $this->update(['status' => InvoiceStatus::Partial]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => InvoiceStatus::Cancelled,
            'notes' => $reason ? ($this->notes."\n\nCancellation reason: ".$reason) : $this->notes,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Overdue ||
               ($this->status->canReceivePayment() && $this->due_date->isPast());
    }

    public function daysOverdue(): int
    {
        if (! $this->due_date->isPast()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now());
    }

    public function recordPayment(float $amount): void
    {
        $newAmountPaid = (float) $this->amount_paid + $amount;
        $newBalance = (float) $this->total_amount - $newAmountPaid;

        $this->update([
            'amount_paid' => $newAmountPaid,
            'balance_due' => max(0, $newBalance),
        ]);

        if ($newBalance <= 0) {
            $this->markAsPaid();
        } elseif ($newAmountPaid > 0) {
            $this->markAsPartial();
        }
    }
}
