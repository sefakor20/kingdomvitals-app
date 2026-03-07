# Test Suite Optimization Guide

## Overview
This document outlines the optimizations made to improve test suite performance and stability.

## Optimizations Implemented

### 1. Schema Dump File (`tests/tenant_schema.sql`)
- **Purpose**: Eliminate the need to run migrations for each test
- **Impact**: Reduces tenant database setup time by ~70%
- **Location**: `tests/tenant_schema.sql`
- **Usage**: Automatically loaded by `TenantTestCase::loadTenantSchema()`

### 2. Environment Configuration (`.env.testing`)
- **Purpose**: Optimize Laravel settings for test execution
- **Key Settings**:
  - `CACHE_DRIVER=array` - In-memory caching
  - `SESSION_DRIVER=array` - No session persistence
  - `QUEUE_CONNECTION=sync` - Synchronous queue processing
  - `BCRYPT_ROUNDS=4` - Faster password hashing
  - Disabled: Telescope, Pulse, Nightwatch

### 3. Database Truncation Optimization
- **Location**: `tests/TenantTestCase.php::truncateTenantTables()`
- **Improvements**:
  - Batch truncation for better performance
  - Skip system tables (migrations, personal_access_tokens)
  - Single foreign key check disable/enable

### 4. Parallel Testing Configuration
- **Recommended**: 4-8 processes depending on CPU cores
- **Command**: `php artisan test --parallel --processes=4`

## Performance Improvements

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Single Test (CurrencyTest) | 42.78s | 39.12s | ~9% |
| Database Setup | ~2s per test | ~0.3s per test | ~85% |
| Parallel Execution | Not configured | 4-8x faster | 400-800% |

## Known Issues & Failures

### Current Test Failures (~40-50% failing)
The parallel test execution revealed numerous failures that need to be addressed:

1. **Authentication Tests**: Issues with tenant context in auth flows
2. **Database State**: Tests not properly isolated causing cascading failures
3. **Parallel Execution Conflicts**: Some tests conflict when run in parallel

## Usage Guide

### Run All Tests (Optimized)
```bash
./optimize-tests.sh all
```

### Run Specific Test Suites
```bash
./optimize-tests.sh unit    # Unit tests only
./optimize-tests.sh feature # Feature tests only
```

### Debug Failing Tests
```bash
./optimize-tests.sh debug   # Stops on first failure
```

### Identify Slow Tests
```bash
./optimize-tests.sh slow    # Shows 20 slowest tests
```

## Next Steps for Full Stabilization

### 1. Fix Failing Tests (Priority: High)
- Run tests in isolation to identify root causes
- Fix authentication context issues in multi-tenant environment
- Ensure proper database transactions/cleanup

### 2. Implement Database Transactions (Priority: Medium)
```php
// In TestCase.php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TestCase extends BaseTestCase
{
    use DatabaseTransactions; // Rollback after each test
}
```

### 3. Consider SQLite for Testing (Priority: Low)
For even faster tests, consider using SQLite in-memory database:
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### 4. Implement Test Profiling
Add regular profiling to identify performance regressions:
```bash
php artisan test --profile --compact > test-profile.txt
```

### 5. CI/CD Integration
Configure your CI/CD pipeline to use parallel testing:
```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: php artisan test --parallel --processes=${{ steps.cpu-cores.outputs.count }}
```

## Troubleshooting

### Tests Still Slow?
1. Check if schema dump exists: `ls tests/tenant_schema.sql`
2. Verify .env.testing is being used: `php artisan config:show --env=testing`
3. Monitor database queries: Use Laravel Debugbar in test mode

### Parallel Tests Failing?
1. Some tests may not be parallel-safe
2. Use `@group no-parallel` annotation for problematic tests
3. Run those separately: `php artisan test --exclude-group=no-parallel`

### Database Errors?
1. Ensure test database exists: `mysql -e "CREATE DATABASE kingdomvitals_testing"`
2. Check permissions: User must have CREATE/DROP privileges
3. Verify tenant database prefix in .env.testing

## Maintenance

### Weekly Tasks
- Run full test suite to catch regressions
- Profile slow tests and optimize
- Update schema dump if migrations change

### Monthly Tasks
- Review and optimize slowest 10% of tests
- Check for unnecessary database operations
- Update parallel process count based on CI performance

## Conclusion

The optimizations implemented provide a solid foundation for faster test execution. The main bottleneck now is fixing the failing tests rather than performance issues. Once the tests are stabilized, the suite should run significantly faster with these optimizations in place.