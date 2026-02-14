# Multi-Currency Support Roadmap

## Overview

Add multi-currency support for both tenant financial records and platform subscription pricing. Tenants can set their default currency (reflecting in donations, expenses, reports), while subscription plans support USD and GHS pricing.

---

## Current State

- **Hardcoded GHS** throughout the application (~150+ locations)
- `SubscriptionPlan` has `price_monthly` and `price_annual` (single currency)
- Tenant financial records store `currency` field but default to 'GHS'
- Paystack integration assumes GHS with kobo conversion
- No currency formatting service exists

---

## Phase 1: Foundation - Currency Infrastructure

### 1.1 Create Currency Enum
**File:** `app/Enums/Currency.php`

```php
enum Currency: string
{
    case GHS = 'GHS';
    case USD = 'USD';

    public function symbol(): string;
    public function name(): string;
    public function decimalPlaces(): int;
    public function subunitMultiplier(): int; // 100 for cents/pesewas
}
```

### 1.2 Create Currency Formatter Service
**File:** `app/Services/CurrencyFormatter.php`

- `format(float $amount, Currency $currency): string`
- `formatWithSymbol(float $amount, Currency $currency): string`
- `toSubunits(float $amount, Currency $currency): int`
- `fromSubunits(int $subunits, Currency $currency): float`

### 1.3 Create Blade Directive
**File:** `app/Providers/AppServiceProvider.php`

```blade
@money($amount, $currency)  → GHS 100.00 or $100.00
```

---

## Phase 2: Subscription Plan Multi-Currency Pricing

### 2.1 Database Migration
**File:** `database/migrations/xxxx_add_multi_currency_to_subscription_plans.php`

Add to `subscription_plans` table:
```php
$table->decimal('price_monthly_usd', 10, 2)->nullable();
$table->decimal('price_annual_usd', 10, 2)->nullable();
// Existing columns become GHS prices (rename for clarity)
// price_monthly → price_monthly_ghs
// price_annual → price_annual_ghs
```

### 2.2 System Settings for Pricing Strategy
**File:** `database/migrations/xxxx_add_currency_settings.php`

Add to `system_settings`:
```php
// pricing_strategy: 'manual' or 'exchange_rate'
// base_currency: 'GHS' or 'USD'
// exchange_rate_usd_to_ghs: decimal (e.g., 15.50)
```

### 2.3 Update SubscriptionPlan Model
**File:** `app/Models/SubscriptionPlan.php`

```php
public function getPriceMonthly(Currency $currency): float
{
    $strategy = SystemSetting::get('pricing_strategy', 'manual');

    if ($strategy === 'manual') {
        // Return stored price for currency
        return $this->{"price_monthly_{$currency->value}"} ?? 0;
    }

    // Exchange rate conversion from base currency
    return $this->convertPrice($this->price_monthly, $currency);
}
```

### 2.4 Update Plan Management UI
**Files:**
- `app/Livewire/SuperAdmin/Plans/PlanIndex.php`
- `resources/views/livewire/super-admin/plans/plan-index.blade.php`

**Manual pricing mode:**
- Show GHS and USD price inputs
- Admin sets each price independently

**Exchange rate mode:**
- Show base currency price only
- Display converted prices (read-only)
- Configurable exchange rate in system settings

### 2.5 Currency System Settings UI
**File:** `app/Livewire/SuperAdmin/SystemSettings.php`

Add settings:
- Pricing strategy toggle (Manual / Exchange Rate)
- Base currency selector (when using exchange rate)
- Exchange rate input (USD ↔ GHS)

### 2.6 Update Plan Checkout
**Files:**
- `app/Livewire/PlanCheckout.php`
- `resources/views/livewire/plan-checkout.blade.php`

- Add currency selector (USD/GHS)
- Calculate price based on selected currency and pricing strategy
- Pass selected currency to Paystack
- Update invoice generation with selected currency

---

## Phase 3: Tenant Currency Settings

### 3.1 Database Migration
**File:** `database/migrations/xxxx_add_currency_to_tenants.php`

```php
$table->string('currency', 3)->default('GHS');
```

### 3.2 Update Tenant Model
**File:** `app/Models/Tenant.php`

- Add `currency` cast to Currency enum
- Add `getCurrency(): Currency` helper

### 3.3 Tenant Settings UI
**Files:**
- `app/Livewire/Settings/ChurchSettings.php`
- `resources/views/livewire/settings/church-settings.blade.php`

- Add currency dropdown in church settings
- Note: Currency change affects new records only

---

## Phase 4: Financial Records Currency Integration

### 4.1 Update Financial Components

Replace hardcoded 'GHS' with tenant currency:

| Component | File |
|-----------|------|
| OfferingIndex | `app/Livewire/Giving/OfferingIndex.php` |
| ExpenseIndex | `app/Livewire/Finance/ExpenseIndex.php` |
| RecurringExpenseIndex | `app/Livewire/Finance/RecurringExpenseIndex.php` |
| BudgetIndex | `app/Livewire/Finance/BudgetIndex.php` |
| PledgeIndex | `app/Livewire/Giving/PledgeIndex.php` |
| PublicGivingForm | `app/Livewire/Giving/PublicGivingForm.php` |

### 4.2 Update Financial Services
**Files:**
- `app/Services/PaystackService.php` - Currency-aware conversion
- `app/Services/AI/FinancialForecastService.php` - Currency in forecasts
- `app/Services/AI/GivingTrendService.php` - Currency in trends

### 4.3 Update Views with @money Directive

Replace manual formatting:
```blade
{{-- Before --}}
{{ $currency }} {{ number_format($amount, 2) }}

{{-- After --}}
@money($amount, $currency)
```

---

## Phase 5: Reporting & Display Updates

### 5.1 Update Financial Reports
- Dashboard widgets show tenant currency
- Export reports include currency
- AI insights display correct currency symbol

### 5.2 Update Email Templates
**Files in:** `resources/views/emails/`
- Invoice emails
- Payment receipts
- Donation confirmations

### 5.3 Update PDF Generation
- Platform invoices
- Donation receipts
- Financial reports

---

## Phase 6: Platform Billing Integration

### 6.1 Update Platform Paystack Service
**File:** `app/Services/PlatformPaystackService.php`

- Accept currency parameter
- Use correct subunit conversion per currency
- Validate currency is supported by Paystack

### 6.2 Update Platform Invoice Generation
**File:** `app/Services/TenantUpgradeService.php`

- Generate invoice in selected currency
- Store currency on PlatformInvoice

### 6.3 Update Billing History Views
- Show currency per transaction
- Handle mixed-currency history

---

## Implementation Order

| Phase | Priority | Dependencies |
|-------|----------|--------------|
| Phase 1 | Critical | None - Foundation |
| Phase 2 | High | Phase 1 |
| Phase 3 | High | Phase 1 |
| Phase 4 | High | Phase 1, 3 |
| Phase 5 | Medium | Phase 1, 3, 4 |
| Phase 6 | High | Phase 1, 2 |

---

## Key Files to Modify

### New Files
- `app/Enums/Currency.php`
- `app/Services/CurrencyFormatter.php`
- `database/migrations/xxxx_add_multi_currency_to_subscription_plans.php`
- `database/migrations/xxxx_add_currency_to_tenants.php`

### Core Modifications
- `app/Models/SubscriptionPlan.php`
- `app/Models/Tenant.php`
- `app/Livewire/PlanCheckout.php`
- `app/Livewire/Settings/ChurchSettings.php`
- `app/Services/PaystackService.php`
- `app/Services/PlatformPaystackService.php`
- `app/Providers/AppServiceProvider.php`

### Component Updates (~15 files)
- All financial Livewire components
- Email templates
- PDF generators

---

## Verification

### Phase 1
```bash
php artisan tinker --execute="
    use App\Enums\Currency;
    echo Currency::GHS->symbol(); // ₵
    echo Currency::USD->symbol(); // $
"
```

### Phase 2
- Create/edit plan with USD + GHS prices
- Checkout with currency selection
- Verify Paystack receives correct currency

### Phase 3
- Change tenant currency in settings
- Verify new donations use tenant currency

### Phase 4
- Create donation in tenant currency
- Verify expense forms show correct currency
- Check financial reports display

### Full Integration
```bash
php artisan test --filter=Currency
php artisan test --filter=Subscription
php artisan test --filter=Donation
```

---

## Notes

- **Supported currencies**: USD and GHS only (can expand later)
- **Existing data**: All existing records remain in GHS
- **No auto-conversion**: Each record stores its currency at creation time
- **Paystack support**: USD and GHS are both supported by Paystack
- **Pricing strategy**: Configurable - either manual per-currency prices or base currency with exchange rate conversion
- **Future expansion**: Enum can easily add EUR, GBP, NGN, etc.
