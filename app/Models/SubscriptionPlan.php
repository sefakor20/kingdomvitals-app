<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportLevel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory, HasUuids;

    /**
     * The database connection to use for this model.
     * SubscriptionPlans are stored in the central database.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annual',
        'max_members',
        'max_branches',
        'storage_quota_gb',
        'sms_credits_monthly',
        'max_households',
        'max_clusters',
        'max_visitors',
        'max_equipment',
        'enabled_modules',
        'features',
        'support_level',
        'is_active',
        'is_default',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'max_members' => 'integer',
            'max_branches' => 'integer',
            'storage_quota_gb' => 'integer',
            'sms_credits_monthly' => 'integer',
            'max_households' => 'integer',
            'max_clusters' => 'integer',
            'max_visitors' => 'integer',
            'max_equipment' => 'integer',
            'enabled_modules' => 'array',
            'features' => 'array',
            'support_level' => SupportLevel::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get the default subscription plan.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Check if a module is enabled in this plan.
     */
    public function hasModule(string $moduleName): bool
    {
        if ($this->enabled_modules === null) {
            return true; // null means all modules enabled
        }

        return in_array($moduleName, $this->enabled_modules);
    }

    /**
     * Check if the plan has unlimited members.
     */
    public function hasUnlimitedMembers(): bool
    {
        return $this->max_members === null;
    }

    /**
     * Check if the plan has unlimited branches.
     */
    public function hasUnlimitedBranches(): bool
    {
        return $this->max_branches === null;
    }

    /**
     * Check if the plan has unlimited storage.
     */
    public function hasUnlimitedStorage(): bool
    {
        return $this->storage_quota_gb === null;
    }

    /**
     * Check if the plan has unlimited households.
     */
    public function hasUnlimitedHouseholds(): bool
    {
        return $this->max_households === null;
    }

    /**
     * Check if the plan has unlimited clusters.
     */
    public function hasUnlimitedClusters(): bool
    {
        return $this->max_clusters === null;
    }

    /**
     * Check if the plan has unlimited visitors.
     */
    public function hasUnlimitedVisitors(): bool
    {
        return $this->max_visitors === null;
    }

    /**
     * Check if the plan has unlimited equipment.
     */
    public function hasUnlimitedEquipment(): bool
    {
        return $this->max_equipment === null;
    }

    /**
     * Check if the plan has unlimited SMS credits.
     */
    public function hasUnlimitedSms(): bool
    {
        return $this->sms_credits_monthly === null;
    }

    /**
     * Get annual savings percentage.
     */
    public function getAnnualSavingsPercent(): float
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }

        $yearlyAtMonthlyRate = $this->price_monthly * 12;
        $savings = $yearlyAtMonthlyRate - $this->price_annual;

        return round(($savings / $yearlyAtMonthlyRate) * 100, 1);
    }
}
