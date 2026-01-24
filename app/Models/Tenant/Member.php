<?php

namespace App\Models\Tenant;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\HouseholdRole;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Observers\MemberObserver;
use Database\Factories\Tenant\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([MemberObserver::class])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    protected $fillable = [
        'primary_branch_id',
        'household_id',
        'age_group_id',
        'household_role',
        'first_name',
        'last_name',
        'maiden_name',
        'middle_name',
        'email',
        'phone',
        'sms_opt_out',
        'date_of_birth',
        'gender',
        'marital_status',
        'profession',
        'employment_status',
        'status',
        'qr_token',
        'qr_token_generated_at',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'hometown',
        'gps_address',
        'joined_at',
        'baptized_at',
        'confirmation_date',
        'notes',
        'previous_congregation',
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'baptized_at' => 'date',
            'confirmation_date' => 'date',
            'qr_token_generated_at' => 'datetime',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'employment_status' => EmploymentStatus::class,
            'status' => MembershipStatus::class,
            'household_role' => HouseholdRole::class,
            'sms_opt_out' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(ChildEmergencyContact::class);
    }

    public function primaryEmergencyContact(): HasOne
    {
        return $this->hasOne(ChildEmergencyContact::class)->where('is_primary', true);
    }

    public function medicalInfo(): HasOne
    {
        return $this->hasOne(ChildMedicalInfo::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(self::class, 'household_id', 'household_id')
            ->where('id', '!=', $this->id);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'cluster_member')
            ->using(ClusterMember::class)
            ->withPivot(['id', 'role', 'joined_at'])
            ->withTimestamps();
    }

    public function assignedVisitors(): HasMany
    {
        return $this->hasMany(Visitor::class, 'assigned_to');
    }

    public function assignedEquipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'assigned_to');
    }

    public function ledClusters(): HasMany
    {
        return $this->hasMany(Cluster::class, 'leader_id');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MemberActivity::class)->latest();
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(MemberUnavailability::class);
    }

    public function dutyRosterPools(): BelongsToMany
    {
        return $this->belongsToMany(DutyRosterPool::class, 'duty_roster_pool_member')
            ->using(DutyRosterPoolMember::class)
            ->withPivot(['id', 'last_assigned_date', 'assignment_count', 'sort_order', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include members who have not opted out of SMS.
     */
    public function scopeNotOptedOutOfSms(Builder $query): Builder
    {
        return $query->where('sms_opt_out', false);
    }

    /**
     * Scope a query to only include members who have opted out of SMS.
     */
    public function scopeOptedOutOfSms(Builder $query): Builder
    {
        return $query->where('sms_opt_out', true);
    }

    /**
     * Check if the member has opted out of SMS.
     */
    public function hasOptedOutOfSms(): bool
    {
        return $this->sms_opt_out === true;
    }

    /**
     * Generate a new QR token for this member.
     */
    public function generateQrToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'qr_token' => $token,
            'qr_token_generated_at' => now(),
        ]);

        return $token;
    }

    /**
     * Get or generate the QR token.
     */
    public function getOrGenerateQrToken(): string
    {
        if (! $this->qr_token) {
            return $this->generateQrToken();
        }

        return $this->qr_token;
    }

    /**
     * Check if the member is a child (under 18).
     */
    public function isChild(): bool
    {
        if (! $this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->age < 18;
    }

    /**
     * Check if the member is a minor (under 13).
     */
    public function isMinor(): bool
    {
        if (! $this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->age < 13;
    }

    /**
     * Scope to get only children members.
     */
    public function scopeChildren(Builder $query): Builder
    {
        return $query->whereNotNull('date_of_birth')
            ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18');
    }

    /**
     * Scope to get only adult members.
     */
    public function scopeAdults(Builder $query): Builder
    {
        return $query->where(function ($q): void {
            $q->whereNull('date_of_birth')
                ->orWhereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 18');
        });
    }

    /**
     * Scope to get children in a specific age group.
     */
    public function scopeInAgeGroup(Builder $query, string $ageGroupId): Builder
    {
        return $query->where('age_group_id', $ageGroupId);
    }

    /**
     * Auto-assign this member to an age group based on their date of birth.
     */
    public function assignAgeGroupByAge(): ?AgeGroup
    {
        if (! $this->date_of_birth || ! $this->isChild()) {
            return null;
        }

        $age = $this->date_of_birth->age;

        $ageGroup = AgeGroup::query()
            ->where('branch_id', $this->primary_branch_id)
            ->where('is_active', true)
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->orderBy('sort_order')
            ->first();

        if ($ageGroup) {
            $this->update(['age_group_id' => $ageGroup->id]);
        }

        return $ageGroup;
    }
}
