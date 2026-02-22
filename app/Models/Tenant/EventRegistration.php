<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\CheckInMethod;
use App\Enums\RegistrationStatus;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
use App\Models\User;
use App\Observers\EventRegistrationObserver;
use Database\Factories\Tenant\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([EventRegistrationObserver::class])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasActivityLogging, HasFactory, HasUuids;

    protected static function newFactory(): EventRegistrationFactory
    {
        return EventRegistrationFactory::new();
    }

    protected $fillable = [
        'event_id',
        'branch_id',
        'member_id',
        'visitor_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'status',
        'registered_at',
        'cancelled_at',
        'cancelled_by',
        'ticket_number',
        'is_paid',
        'price_paid',
        'requires_payment',
        'payment_transaction_id',
        'payment_reference',
        'check_in_time',
        'check_out_time',
        'check_in_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'check_in_method' => CheckInMethod::class,
            'registered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_paid' => 'boolean',
            'price_paid' => 'decimal:2',
            'requires_payment' => 'boolean',
            'check_in_time' => 'datetime:H:i:s',
            'check_out_time' => 'datetime:H:i:s',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            RegistrationStatus::Cancelled,
        ]);
    }

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopeAttended(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Attended);
    }

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Registered);
    }

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopeRequiresPayment(Builder $query): Builder
    {
        return $query->where('requires_payment', true)
            ->where('is_paid', false);
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getAttendeeNameAttribute(): string
    {
        if ($this->member) {
            return $this->member->fullName();
        }

        if ($this->visitor) {
            return $this->visitor->fullName();
        }

        return $this->guest_name ?? 'Unknown';
    }

    public function getAttendeeEmailAttribute(): ?string
    {
        if ($this->member) {
            return $this->member->email;
        }

        if ($this->visitor) {
            return $this->visitor->email;
        }

        return $this->guest_email;
    }

    public function getAttendeePhoneAttribute(): ?string
    {
        if ($this->member) {
            return $this->member->phone;
        }

        if ($this->visitor) {
            return $this->visitor->phone;
        }

        return $this->guest_phone;
    }

    public function getAttendeeTypeAttribute(): string
    {
        if ($this->member_id) {
            return 'member';
        }

        if ($this->visitor_id) {
            return 'visitor';
        }

        return 'guest';
    }

    public function getIsCheckedInAttribute(): bool
    {
        return $this->check_in_time !== null;
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->check_out_time !== null;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function generateTicketNumber(): string
    {
        $prefix = 'EVT';
        $eventShort = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->event->name), 0, 3));
        $sequence = $this->event->registrations()->count() + 1;

        $ticketNumber = sprintf('%s-%s-%04d', $prefix, $eventShort, $sequence);

        $this->update(['ticket_number' => $ticketNumber]);

        return $ticketNumber;
    }

    public function markAsAttended(?CheckInMethod $method = null): void
    {
        $this->update([
            'status' => RegistrationStatus::Attended,
            'check_in_time' => now()->format('H:i:s'),
            'check_in_method' => $method ?? CheckInMethod::Manual,
        ]);
    }

    public function markAsCheckedOut(): void
    {
        $this->update([
            'check_out_time' => now()->format('H:i:s'),
        ]);
    }

    public function markAsCancelled(?User $cancelledBy = null): void
    {
        $this->update([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy?->id,
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update([
            'status' => RegistrationStatus::NoShow,
        ]);
    }

    public function markAsPaid(PaymentTransaction $transaction): void
    {
        $this->update([
            'is_paid' => true,
            'payment_reference' => $transaction->paystack_reference,
            'payment_transaction_id' => $transaction->id,
            'requires_payment' => false,
        ]);

        if (! $this->ticket_number) {
            $this->generateTicketNumber();
        }
    }

    // ==========================================
    // ACTIVITY LOGGING
    // ==========================================

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::EventRegistration;
    }

    public function getActivitySubjectName(): string
    {
        return $this->attendee_name.' - '.$this->event->name;
    }

    public function getActivityBranchId(): string
    {
        return $this->branch_id;
    }
}
