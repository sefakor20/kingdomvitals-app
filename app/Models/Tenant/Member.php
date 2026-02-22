<?php

namespace App\Models\Tenant;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\HouseholdRole;
use App\Enums\LifecycleStage;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Enums\SmsEngagementLevel;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
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
    use HasActivityLogging, HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Member $member): void {
            if (empty($member->membership_number)) {
                $member->membership_number = static::generateMembershipNumber();
            }
        });
    }

    /**
     * Generate a unique membership number in the format MEM-XXXX.
     */
    public static function generateMembershipNumber(): string
    {
        $lastNumber = static::withTrashed()
            ->whereNotNull('membership_number')
            ->where('membership_number', 'like', 'MEM-%')
            ->orderByRaw('CAST(SUBSTRING(membership_number, 5) AS UNSIGNED) DESC')
            ->value('membership_number');

        $nextNum = $lastNumber ? (int) substr($lastNumber, 4) + 1 : 1;

        return 'MEM-'.str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'primary_branch_id',
        'membership_number',
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
        'churn_risk_score',
        'churn_risk_factors',
        'churn_risk_calculated_at',
        'attendance_anomaly_score',
        'attendance_anomaly_detected_at',
        'sms_engagement_score',
        'sms_engagement_level',
        'sms_optimal_send_hour',
        'sms_optimal_send_day',
        'sms_response_rate',
        'sms_last_engaged_at',
        'sms_total_received',
        'sms_total_delivered',
        'sms_engagement_calculated_at',
        'lifecycle_stage',
        'lifecycle_stage_changed_at',
        'lifecycle_stage_factors',
        'giving_consistency_score',
        'giving_growth_rate',
        'donor_tier',
        'giving_trend',
        'giving_analyzed_at',
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
            'churn_risk_score' => 'decimal:2',
            'churn_risk_factors' => 'array',
            'churn_risk_calculated_at' => 'datetime',
            'attendance_anomaly_score' => 'decimal:2',
            'attendance_anomaly_detected_at' => 'datetime',
            'sms_engagement_score' => 'decimal:2',
            'sms_engagement_level' => SmsEngagementLevel::class,
            'sms_optimal_send_hour' => 'integer',
            'sms_optimal_send_day' => 'integer',
            'sms_response_rate' => 'decimal:2',
            'sms_last_engaged_at' => 'datetime',
            'sms_total_received' => 'integer',
            'sms_total_delivered' => 'integer',
            'sms_engagement_calculated_at' => 'datetime',
            'lifecycle_stage' => LifecycleStage::class,
            'lifecycle_stage_changed_at' => 'datetime',
            'lifecycle_stage_factors' => 'array',
            'giving_consistency_score' => 'integer',
            'giving_growth_rate' => 'decimal:2',
            'giving_analyzed_at' => 'datetime',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Member;
    }

    public function getActivitySubjectName(): string
    {
        return $this->fullName();
    }

    public function getActivityBranchId(): string
    {
        return $this->primary_branch_id;
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

    /**
     * Scope to get members by lifecycle stage.
     */
    public function scopeInLifecycleStage(Builder $query, LifecycleStage $stage): Builder
    {
        return $query->where('lifecycle_stage', $stage->value);
    }

    /**
     * Scope to get members needing attention (at-risk, disengaging, dormant).
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('lifecycle_stage', [
            LifecycleStage::AtRisk->value,
            LifecycleStage::Disengaging->value,
            LifecycleStage::Dormant->value,
        ]);
    }

    /**
     * Scope to get actively engaged members.
     */
    public function scopeEngaged(Builder $query): Builder
    {
        return $query->whereIn('lifecycle_stage', [
            LifecycleStage::NewMember->value,
            LifecycleStage::Growing->value,
            LifecycleStage::Engaged->value,
        ]);
    }

    /**
     * Check if the member needs attention based on lifecycle stage.
     */
    public function needsLifecycleAttention(): bool
    {
        return $this->lifecycle_stage?->needsAttention() ?? false;
    }

    /**
     * Check if the member is actively engaged.
     */
    public function isActivelyEngaged(): bool
    {
        return $this->lifecycle_stage?->isActive() ?? false;
    }
}
