<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
use App\Observers\EventObserver;
use Database\Factories\Tenant\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([EventObserver::class])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasActivityLogging, HasFactory, HasUuids;

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'organizer_id',
        'name',
        'description',
        'event_type',
        'category',
        'starts_at',
        'ends_at',
        'location',
        'address',
        'city',
        'country',
        'capacity',
        'allow_registration',
        'registration_opens_at',
        'registration_closes_at',
        'is_paid',
        'price',
        'currency',
        'requires_ticket',
        'status',
        'is_public',
        'visibility',
        'notes',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'status' => EventStatus::class,
            'visibility' => EventVisibility::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'capacity' => 'integer',
            'allow_registration' => 'boolean',
            'is_paid' => 'boolean',
            'price' => 'decimal:2',
            'requires_ticket' => 'boolean',
            'is_public' => 'boolean',
            'reminder_sent_at' => 'datetime',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'organizer_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->where('status', EventStatus::Published);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where('status', EventStatus::Ongoing);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNotNull('ends_at')
                ->where('ends_at', '<', now());
        })->orWhere(function (Builder $q): void {
            $q->whereNull('ends_at')
                ->where('starts_at', '<', now()->subDay());
        });
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [
            EventStatus::Published,
            EventStatus::Ongoing,
            EventStatus::Completed,
        ]);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->where('visibility', EventVisibility::Public);
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getRegisteredCountAttribute(): int
    {
        return $this->registrations()
            ->whereNotIn('status', ['cancelled'])
            ->count();
    }

    public function getAttendedCountAttribute(): int
    {
        return $this->registrations()
            ->where('status', 'attended')
            ->count();
    }

    public function getAvailableSpotsAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->registered_count);
    }

    public function getIsFullAttribute(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->registered_count >= $this->capacity;
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        if (! $this->allow_registration) {
            return false;
        }

        if ($this->is_full) {
            return false;
        }

        if ($this->status !== EventStatus::Published) {
            return false;
        }

        $now = now();

        if ($this->registration_opens_at && $now < $this->registration_opens_at) {
            return false;
        }

        if ($this->registration_closes_at && $now > $this->registration_closes_at) {
            return false;
        }

        return true;
    }

    public function getFormattedPriceAttribute(): string
    {
        if (! $this->is_paid || $this->price === null) {
            return 'Free';
        }

        return $this->currency.' '.number_format((float) $this->price, 2);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function publish(): void
    {
        $this->update(['status' => EventStatus::Published]);
    }

    public function cancel(): void
    {
        $this->update(['status' => EventStatus::Cancelled]);
    }

    public function complete(): void
    {
        $this->update(['status' => EventStatus::Completed]);
    }

    public function canRegister(?Member $member = null, ?Visitor $visitor = null): bool
    {
        if (! $this->is_registration_open) {
            return false;
        }

        // Check for duplicate registration
        if ($member) {
            return ! $this->registrations()
                ->where('member_id', $member->id)
                ->whereNotIn('status', ['cancelled'])
                ->exists();
        }

        if ($visitor) {
            return ! $this->registrations()
                ->where('visitor_id', $visitor->id)
                ->whereNotIn('status', ['cancelled'])
                ->exists();
        }

        return true;
    }

    // ==========================================
    // ACTIVITY LOGGING
    // ==========================================

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Event;
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
