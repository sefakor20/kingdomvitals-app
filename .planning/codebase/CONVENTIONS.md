# Coding Conventions

**Analysis Date:** 2026-02-03

## Naming Patterns

**Files:**
- Classes: PascalCase (e.g., `ProrationService.php`, `Member.php`, `AttendanceIndex.php`)
- Single class per file, namespace matches directory structure
- Model classes in `app/Models/` or `app/Models/Tenant/` for tenant-scoped models
- Service classes in `app/Services/` ending with `Service` suffix
- Livewire components in `app/Livewire/` organized by feature subdirectories

**Functions/Methods:**
- camelCase for all public and private methods
- Action names are descriptive: `calculatePlanChange()`, `getDailyRate()`, `shouldApplyProration()`
- Protected helper methods use underscores for clarity: `noProrationResult()`, `validateWebhookSignature()`
- Boolean methods prefix with "is", "has", "should", or "can": `shouldApplyProration()`, `tenantDatabaseHasSchema()`

**Variables:**
- camelCase for properties and local variables
- Descriptive names preferred over abbreviations: `$daysRemaining` not `$days_rem`
- Public properties declared before methods: e.g., `public Branch $branch;`
- Filter/flag properties clearly named: `$showDeleteModal`, `$typeFilter`, `$methodFilter`
- Collection variables use plural forms: `$attendanceRecords`, `$searchResults`

**Types:**
- Enum names use TitleCase: `BillingCycle`, `CheckInMethod`, `BranchRole`, `EmploymentStatus`
- Enum cases are TitleCase: `Monthly`, `Annual`, `Staff`, `Admin`

**Traits:**
- Named with "Concerns" prefix in subdirectory: `app/Livewire/Concerns/HasQuotaComputed`, `HasFilterableQuery`
- Traits with cohesive responsibilities grouped in `Concerns` folders

## Code Style

**Formatting:**
- PHP 8.3 with `declare(strict_types=1);` on first line of every PHP file
- 4-space indentation
- Curly braces on same line for control structures (PSR-12 style)
- Type declarations required on all method parameters and return types
- Use strict type checking throughout

**Linting:**
- Laravel Rector configured for code quality with: deadCode, codeQuality, typeDeclarations, privatization, earlyReturn
- Rector configuration at `rector.php`
- No explicit Pint configuration; uses Laravel default formatting

## Import Organization

**Order:**
1. Core Laravel/PHP imports (Illuminate, SPL)
2. Domain-specific classes (App\Enums, App\Models, App\Services)
3. Third-party packages (Carbon, Livewire attributes)

**Path Aliases:**
- No explicit aliases configured; imports use full namespaces
- Models namespaced under `App\Models` for platform models and `App\Models\Tenant` for tenant-scoped models

**Example from `ProrationService.php`:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
```

## Error Handling

**Patterns:**
- Authorization checks via `$this->authorize()` in Livewire components
- Form validation through `FormRequest` classes in `app/Http/Requests/`
- Custom authorization in `authorize()` method of Form Requests
- Return type hints with nullable types where appropriate: `public function getLogoUrl(string $size = 'small'): ?string`
- Exception handling using try-catch with specific exception handling
- Log warnings for security issues: `Log::warning('...')` in webhook validation

**Example from `TextTangoWebhookRequest.php`:**
```php
public function authorize(): bool
{
    return $this->validateWebhookSignature();
}

protected function validateWebhookSignature(): bool
{
    $webhookSecret = config('services.texttango.webhook_secret');

    if (empty($webhookSecret)) {
        Log::warning('TextTango webhook: No webhook secret configured');
        return app()->environment('local', 'testing');
    }

    // Verify HMAC signature...
}
```

## Logging

**Framework:** Laravel's `Log` facade using `Illuminate\Support\Facades\Log`

**Patterns:**
- Use `Log::warning()` for security/authorization issues
- Log in webhook handlers for audit trails
- Environment-aware logging in development vs. production

## Comments

**When to Comment:**
- Avoided in favor of self-documenting code
- Section dividers with full-width comments for major logical blocks:
```php
// ============================================
// LOGO METHODS
// ============================================
```

**PHPDoc/TSDoc:**
- Required on public methods and complex functions
- Include array shape type definitions for complex return arrays
- Example from `ProrationService.php`:
```php
/**
 * Calculate proration for a plan change.
 *
 * @return array{
 *     days_remaining: int,
 *     days_used: int,
 *     days_in_period: int,
 *     old_plan_credit: float,
 *     new_plan_cost: float,
 *     amount_due: float,
 *     credit_generated: float,
 *     change_type: string,
 *     old_daily_rate: float,
 *     new_daily_rate: float
 * }
 */
```

## Function Design

**Size:**
- Methods are kept focused with single responsibility
- Complex logic broken into smaller protected helper methods
- Service classes aggregate related business logic

**Parameters:**
- Type hints on all parameters
- Nullable types used explicitly: `?string $path = null`
- Eloquent models passed directly instead of IDs when relationships exist
- Enums passed as typed parameters: `BillingCycle $cycle`

**Return Values:**
- Explicit return type declarations on all methods
- Array returns documented with shape types in PHPDoc
- Nullable returns use `?Type` syntax

## Module Design

**Exports:**
- Models export relationships as public methods with return type hints
- Services are stateless classes with public action methods
- Livewire components use public properties for state and properties marked with `#[Computed]` for derived state

**Barrel Files:**
- Not used in this codebase; direct imports from classes preferred

**Class Practices:**
- Constructor property promotion used: `public function __construct(public ProrationService $prorationService)`
- Traits used for cross-cutting concerns (HasFactory, SoftDeletes, HasUuids)
- Model relationships declared as proper methods with return types
- Model casts defined in `casts()` method instead of property (Laravel 12 style)

**Example Model Structure from `Tenant.php`:**
```php
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids, SoftDeletes;

    protected $fillable = [...];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'status' => TenantStatus::class,
            'logo' => 'array',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_id');
    }
}
```

**Livewire Component Structure:**
- State properties declared at class level
- Computed properties use `#[Computed]` attribute
- Layout defined with `#[Layout]` attribute
- Event listeners use `#[On]` attribute
- Query building in computed properties to avoid N+1 queries
- Proper pagination with `WithPagination` trait

**Example from `AttendanceIndex.php`:**
```php
#[Layout('components.layouts.app')]
class AttendanceIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

    public Branch $branch;
    public string $search = '';
    public ?string $serviceFilter = null;
    public bool $showDeleteModal = false;

    #[Computed]
    public function attendanceRecords(): LengthAwarePaginator
    {
        // Query building logic...
    }
}
```

---

*Convention analysis: 2026-02-03*
