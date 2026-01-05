<?php

namespace App\Models\Tenant;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'receipt_number',
        'receipt_sent_at',
        'donor_name',
        'donor_email',
        'donor_phone',
        'paystack_customer_code',
        'notes',
        'is_anonymous',
        'is_recurring',
        'recurring_interval',
        'paystack_subscription_code',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'donation_date' => 'date',
            'receipt_sent_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'is_recurring' => 'boolean',
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

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get or generate the receipt number for this donation.
     */
    public function getReceiptNumber(): string
    {
        if (! $this->receipt_number) {
            $this->receipt_number = app(\App\Services\DonationReceiptService::class)->generateReceiptNumber($this);
            $this->save();
        }

        return $this->receipt_number;
    }

    /**
     * Get the donor's display name, respecting anonymity.
     */
    public function getDonorDisplayName(): string
    {
        if ($this->is_anonymous) {
            return __('Anonymous Donor');
        }

        return $this->member?->fullName() ?? $this->donor_name ?? __('Unknown Donor');
    }

    /**
     * Get the donor's email address if available.
     */
    public function getDonorEmail(): ?string
    {
        return $this->member?->email ?? $this->donor_email;
    }

    /**
     * Check if a receipt can be sent for this donation.
     */
    public function canSendReceipt(): bool
    {
        return ! $this->is_anonymous && $this->getDonorEmail() !== null;
    }

    /**
     * Check if this is an online payment (via Paystack).
     */
    public function isOnlinePayment(): bool
    {
        return $this->payment_method === PaymentMethod::Paystack;
    }
}
