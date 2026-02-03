# Testing Patterns

**Analysis Date:** 2026-02-03

## Test Framework

**Runner:**
- Pest v4 - configured in `tests/Pest.php`
- PHPUnit v12 as underlying test framework
- Config: `phpunit.xml`

**Assertion Library:**
- Pest's expect() API with chainable assertions
- Custom expectation: `expect()->extend('toBeOne', fn() => $this->toBe(1))`

**Run Commands:**
```bash
php artisan test                    # Run all tests
php artisan test --compact          # Run with compact output
php artisan test --filter=testName  # Run specific test
composer test                       # Full test suite from composer script
```

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/` organized by domain (e.g., `tests/Feature/Attendance/`, `tests/Feature/Settings/`)
- Unit tests: `tests/Unit/` with matching namespace structure (e.g., `tests/Unit/Services/`)
- Test base classes: `tests/TestCase.php`, `tests/TenantTestCase.php`

**Naming:**
- Test files end with `Test.php` suffix: `AttendanceIndexTest.php`, `ProrationServiceTest.php`
- Test functions use lowercase: `test('description of what it tests', function() { ... })`
- Test descriptions are human-readable narrative: "admin can delete attendance record"

**Structure:**
```
tests/
├── Feature/
│   ├── Attendance/
│   │   ├── AttendanceIndexTest.php
│   │   ├── AttendanceManagementTest.php
│   │   ├── LiveCheckInTest.php
│   │   └── AttendanceAnalyticsTest.php
│   └── Settings/
│       ├── SubscriptionCancellationTest.php
│       └── PaymentHistoryTest.php
├── Unit/
│   └── Services/
│       └── ProrationServiceTest.php
├── Pest.php
├── TestCase.php
└── TenantTestCase.php
```

## Test Structure

**Suite Organization:**
- Global setup in `tests/Pest.php` configures test base class and uses RefreshDatabase trait
- Feature tests extend `Tests\TestCase` automatically
- Multi-tenant tests use `TenantTestCase` trait for isolation

**Patterns:**
- `beforeEach()` runs setup before each test (factory creation, tenant setup)
- `afterEach()` runs cleanup after each test (tenant teardown)
- Comments with full-width section dividers group related tests:
```php
// ============================================
// PAGE ACCESS TESTS
// ============================================
```

**Example Structure from `AttendanceIndexTest.php`:**
```php
<?php

use App\Enums\BranchRole;
use App\Livewire\Attendance\AttendanceIndex;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $this->member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view attendance page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/attendance")
        ->assertOk()
        ->assertSeeLivewire(AttendanceIndex::class);
});
```

## Mocking

**Framework:** Mockery with Laravel integration

**Patterns:**
```php
// Mock model with partial (keep some real behavior)
$tenant = Mockery::mock(Tenant::class)->makePartial();
$tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('plan-1');
$tenant->shouldReceive('getAttribute')->with('current_period_end')
    ->andReturn(Carbon::parse('2026-01-31'));

// For relationships
$tenant->shouldReceive('getRelationValue')->with('subscriptionPlan')->andReturn($currentPlan);
```

**What to Mock:**
- External API services and clients
- Complex model relationships in unit tests
- Time-dependent logic using Carbon::setTestNow()

**What NOT to Mock:**
- Eloquent models in feature tests (use factories instead)
- Database queries in integration tests
- Authorization policies (test them as-is)

**Example from `ProrationServiceTest.php`:**
```php
it('should apply proration when in active billing period', function (): void {
    Carbon::setTestNow('2026-01-15');

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('some-plan-id');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')
        ->andReturn(Carbon::parse('2026-01-01'));
    $tenant->shouldReceive('getAttribute')->with('current_period_end')
        ->andReturn(Carbon::parse('2026-01-31'));

    expect($this->prorationService->shouldApplyProration($tenant))->toBeTrue();

    Carbon::setTestNow();
});
```

## Fixtures and Factories

**Test Data:**
- Use Laravel factories for all model creation: `User::factory()->create()`
- Factory states for variations: `Branch::factory()->main()->create()`
- Override specific attributes inline: `Member::factory()->create(['first_name' => 'John'])`
- Batch creation with count: `Attendance::factory()->count(3)->create([...])`

**Location:**
- Factories in `database/factories/` organized by namespace (e.g., `database/factories/Tenant/MemberFactory.php`)
- No manual seed data; factories are the source of test data

**Example Pattern:**
```php
beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $this->member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::factory()->count(5)->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => fn() => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
    ]);
});
```

## Coverage

**Requirements:** No explicit coverage threshold enforced

**View Coverage:**
```bash
php artisan test --coverage           # Generate coverage report
php artisan test --coverage-html      # Generate HTML coverage report
```

## Test Types

**Unit Tests:**
- Location: `tests/Unit/`
- Scope: Single class/method in isolation
- Approach: Mock all dependencies, test logic independently
- Example: `ProrationServiceTest.php` tests calculation logic without database
- Uses `beforeEach()` to instantiate service under test

**Feature Tests:**
- Location: `tests/Feature/`
- Scope: Full request/component flow through the application
- Approach: Use factories, test with real database, include authorization
- Testing HTTP responses with `->get()`, `->post()`, etc.
- Testing Livewire components with `Livewire::test(Component::class)`
- Include assertions on authorization, validation, and side effects

**E2E Tests:**
- Not implemented in this codebase

## Common Patterns

**Async Testing:**
- Not applicable in Pest/Laravel context (synchronous tests)
- Queue testing uses sync driver in phpunit.xml: `<env name="QUEUE_CONNECTION" value="sync"/>`

**Error Testing:**
```php
test('user without branch access cannot view attendance page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/attendance")
        ->assertForbidden();
});

test('should not apply proration when no subscription', function (): void {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn(null);

    expect($this->prorationService->shouldApplyProration($tenant))->toBeFalse();
});
```

**Authorization Testing:**
- Test with `$this->actingAs($user)` to authenticate before making requests
- Test unauthorized access explicitly (forbidden responses)
- Test role-based access: create users with specific roles and test each role

**Livewire Component Testing:**
```php
test('can filter attendance by search term', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('search', 'Johnathan')
        ->assertSee('Johnathan')
        ->assertDontSee('Jane');
});
```

**Multi-Tenant Testing:**
```php
uses(TenantTestCase::class);  // Trait that provides test tenant setup

beforeEach(function (): void {
    $this->setUpTestTenant();  // Creates isolated tenant database
    // ... create test data in tenant context
});

afterEach(function (): void {
    $this->tearDownTestTenant();  // Cleanup tenant
});
```

## Test Database Configuration

**From `phpunit.xml`:**
- Testing database: `kingdomvitals_testing`
- Database connection: `mysql`
- Migrations run automatically via `RefreshDatabase` trait
- Special test tenant setup in `TenantTestCase` uses SQL schema dump for performance
- Schema dump location: `tests/tenant_schema.sql`

**Tenant Test Performance Optimization:**
- Fixed test tenant ID: `test-tenant-fixed`
- Reuses pre-migrated database across tests
- Truncates tables between tests instead of dropping/recreating
- Fallback to running migrations if schema dump missing

## Test Environment Configuration

**Environment Variables (from `phpunit.xml`):**
```php
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="kingdomvitals_testing"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="BCRYPT_ROUNDS" value="4"/>  // Faster hashing in tests
<env name="MAIL_MAILER" value="array"/>
<env name="BROADCAST_CONNECTION" value="null"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
<env name="NIGHTWATCH_ENABLED" value="false"/>
```

## Test Assertions

**Common Assertions Used:**
- `->assertOk()` - HTTP 200 response
- `->assertForbidden()` - HTTP 403 response
- `->assertRedirect('/path')` - HTTP redirect
- `->assertSeeLivewire(Component::class)` - Component rendered
- `->assertSee('text')` - Text present in response
- `->assertDontSee('text')` - Text absent from response
- `expect($value)->toBe($expected)` - Equality
- `expect($value)->toBeNull()` - Null check
- `expect($value)->toBeTrue()` / `->toBeFalse()` - Boolean
- `expect($collection->count())->toBe(5)` - Collection size
- `->assertSet('propertyName', $value)` - Livewire property assertion
- `->assertDispatched('eventName')` - Event dispatch verification

---

*Testing analysis: 2026-02-03*
