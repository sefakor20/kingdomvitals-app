<?php

namespace App\Models\Tenant;

use App\Enums\PledgeFrequency;
use App\Enums\PledgeStatus;
use App\Enums\SubjectType;
use App\Models\Concerns\HasActivityLogging;
use App\Observers\PledgeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PledgeObserver::class])]
class Pledge extends Model
{
    use HasActivityLogging, HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'pledge_campaign_id',
        'member_id',
        'campaign_name',
        'amount',
        'currency',
        'frequency',
        'start_date',
        'end_date',
        'amount_fulfilled',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_fulfilled' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'frequency' => PledgeFrequency::class,
            'status' => PledgeStatus::class,
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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PledgeCampaign::class, 'pledge_campaign_id');
    }

    public function remainingAmount(): float
    {
        return $this->amount - $this->amount_fulfilled;
    }

    public function completionPercentage(): float
    {
        if ($this->amount == 0) {
            return 0;
        }

        return round(($this->amount_fulfilled / $this->amount) * 100, 2);
    }

    public function getActivitySubjectType(): SubjectType
    {
        return SubjectType::Pledge;
    }

    public function getActivitySubjectName(): string
    {
        $memberName = $this->member?->fullName() ?? 'Unknown';
        $campaignName = $this->campaign?->name ?? $this->campaign_name ?? 'General';

        return "{$memberName} - {$campaignName}";
    }

    public function getActivityBranchId(): string
    {
        return $this->branch_id;
    }
}
