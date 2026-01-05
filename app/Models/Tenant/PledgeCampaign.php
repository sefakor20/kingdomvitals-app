<?php

namespace App\Models\Tenant;

use App\Enums\CampaignCategory;
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PledgeCampaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'category',
        'goal_amount',
        'goal_participants',
        'currency',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'goal_participants' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'category' => CampaignCategory::class,
            'status' => CampaignStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    public function totalPledged(): float
    {
        return (float) $this->pledges()->sum('amount');
    }

    public function totalFulfilled(): float
    {
        return (float) $this->pledges()->sum('amount_fulfilled');
    }

    public function participantCount(): int
    {
        return $this->pledges()->count();
    }

    public function amountProgress(): float
    {
        if (! $this->goal_amount || $this->goal_amount == 0) {
            return 0;
        }

        return round(($this->totalPledged() / $this->goal_amount) * 100, 2);
    }

    public function participantProgress(): float
    {
        if (! $this->goal_participants || $this->goal_participants == 0) {
            return 0;
        }

        return round(($this->participantCount() / $this->goal_participants) * 100, 2);
    }

    public function fulfillmentProgress(): float
    {
        $totalPledged = $this->totalPledged();

        if ($totalPledged == 0) {
            return 0;
        }

        return round(($this->totalFulfilled() / $totalPledged) * 100, 2);
    }

    public function isActive(): bool
    {
        return $this->status === CampaignStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === CampaignStatus::Draft;
    }

    public function isCompleted(): bool
    {
        return $this->status === CampaignStatus::Completed;
    }
}
