<?php

namespace App\Models\Tenant;

use App\Enums\SubjectType;
use App\Enums\VisitorStatus;
use App\Models\Concerns\HasActivityLogging;
use App\Observers\VisitorObserver;
use Database\Factories\Tenant\VisitorFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([VisitorObserver::class])]
class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasActivityLogging, HasFactory, HasUuids;

    protected static function newFactory(): VisitorFactory
    {
        return VisitorFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'visit_date',
        'status',
        'how_did_you_hear',
        'notes',
        'assigned_to',
        'next_follow_up_at',
        'last_follow_up_at',
        'follow_up_count',
        'is_converted',
        'converted_member_id',
        'conversion_score',
        'conversion_factors',
        'conversion_score_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'is_converted' => 'boolean',
            'status' => VisitorStatus::class,
            'next_follow_up_at' => 'datetime',
            'last_follow_up_at' => 'datetime',
            'follow_up_count' => 'integer',
            'conversion_score' => 'decimal:2',
            'conversion_factors' => 'array',
            'conversion_score_calculated_at' => 'datetime',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_to');
    }

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_member_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(VisitorFollowUp::class)->orderBy('created_at', 'desc');
    }

    public function pendingFollowUps(): HasMany
    {
        return $this->hasMany(VisitorFollowUp::class)
            ->where('outcome', 'pending')
            ->orderBy('scheduled_at', 'asc');
    }

    public function latestFollowUp(): HasMany
    {
        return $this->hasMany(VisitorFollowUp::class)
            ->where('outcome', '!=', 'pending')
            ->latest('completed_at')
            ->take(1);
    }

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Visitor;
    }

    public function getActivitySubjectName(): string
    {
        return $this->fullName();
    }

    public function getActivityBranchId(): string
    {
        return $this->branch_id;
    }
}
