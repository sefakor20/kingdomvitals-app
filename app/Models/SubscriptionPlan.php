<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
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
        'price_monthly_usd',
        'price_annual_usd',
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
            'price_monthly_usd' => 'decimal:2',
            'price_annual_usd' => 'decimal:2',
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
     * Get the monthly price for a specific currency.
     */
    public function getPriceMonthly(Currency|string $currency = Currency::GHS): float
    {
        $currency = $currency instanceof Currency ? $currency : Currency::fromString($currency);
        $strategy = SystemSetting::get('pricing_strategy', 'manual');

        if ($strategy === 'manual') {
            return $this->getManualPrice('monthly', $currency);
        }

        return $this->getConvertedPrice('monthly', $currency);
    }

    /**
     * Get the annual price for a specific currency.
     */
    public function getPriceAnnual(Currency|string $currency = Currency::GHS): float
    {
        $currency = $currency instanceof Currency ? $currency : Currency::fromString($currency);
        $strategy = SystemSetting::get('pricing_strategy', 'manual');

        if ($strategy === 'manual') {
            return $this->getManualPrice('annual', $currency);
        }

        return $this->getConvertedPrice('annual', $currency);
    }

    /**
     * Get formatted monthly price for display.
     */
    public function getFormattedPriceMonthly(Currency|string $currency = Currency::GHS): string
    {
        $currency = $currency instanceof Currency ? $currency : Currency::fromString($currency);
        $price = $this->getPriceMonthly($currency);

        return $currency->symbol().number_format($price, 2);
    }

    /**
     * Get formatted annual price for display.
     */
    public function getFormattedPriceAnnual(Currency|string $currency = Currency::GHS): string
    {
        $currency = $currency instanceof Currency ? $currency : Currency::fromString($currency);
        $price = $this->getPriceAnnual($currency);

        return $currency->symbol().number_format($price, 2);
    }

    /**
     * Check if this plan has pricing set for a currency.
     */
    public function hasPricing(Currency|string $currency): bool
    {
        $currency = $currency instanceof Currency ? $currency : Currency::fromString($currency);

        return match ($currency) {
            Currency::GHS => $this->price_monthly > 0 || $this->price_annual > 0,
            Currency::USD => $this->price_monthly_usd > 0 || $this->price_annual_usd > 0,
        };
    }

    /**
     * Get price using manual pricing (stored price per currency).
     */
    protected function getManualPrice(string $cycle, Currency $currency): float
    {
        $column = $cycle === 'monthly' ? 'price_monthly' : 'price_annual';

        if ($currency === Currency::USD) {
            $column .= '_usd';
        }

        return (float) ($this->{$column} ?? 0);
    }

    /**
     * Get price using exchange rate conversion from base currency.
     */
    protected function getConvertedPrice(string $cycle, Currency $currency): float
    {
        $baseCurrency = Currency::fromString(
            SystemSetting::get('base_currency', 'GHS')
        );

        // Get base price
        $baseColumn = $cycle === 'monthly' ? 'price_monthly' : 'price_annual';
        if ($baseCurrency === Currency::USD) {
            $baseColumn .= '_usd';
        }

        $basePrice = (float) ($this->{$baseColumn} ?? 0);

        // If same currency, return base price
        if ($currency === $baseCurrency) {
            return $basePrice;
        }

        // Convert using exchange rate
        $exchangeRate = (float) SystemSetting::get('exchange_rate_usd_to_ghs', 15.0);

        if ($baseCurrency === Currency::USD && $currency === Currency::GHS) {
            return round($basePrice * $exchangeRate, 2);
        }

        if ($baseCurrency === Currency::GHS && $currency === Currency::USD) {
            return round($basePrice / $exchangeRate, 2);
        }

        return $basePrice;
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
     * Get annual savings percentage for a specific currency.
     */
    public function getAnnualSavingsPercent(Currency|string $currency = Currency::GHS): float
    {
        $monthly = $this->getPriceMonthly($currency);

        if ($monthly <= 0) {
            return 0;
        }

        $annual = $this->getPriceAnnual($currency);
        $yearlyAtMonthlyRate = $monthly * 12;
        $savings = $yearlyAtMonthlyRate - $annual;

        return round(($savings / $yearlyAtMonthlyRate) * 100, 1);
    }
}
