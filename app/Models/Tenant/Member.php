<?php

namespace App\Models\Tenant;

use App\Enums\Gender;
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
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'sms_opt_out',
        'date_of_birth',
        'gender',
        'marital_status',
        'status',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'joined_at',
        'baptized_at',
        'notes',
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'baptized_at' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'status' => MembershipStatus::class,
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
}
