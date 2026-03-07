# Test Suite Fix Guide

## Immediate Fix Applied

The test suite has been temporarily configured to use migrations instead of the schema dump to ensure all tables are created correctly. This fixes the failing tests but impacts performance.

## To Restore Fast Performance

### Option 1: Fix Schema Loading (Recommended)

Replace the `loadTenantSchema()` method in `tests/TenantTestCase.php`:

```php
protected function loadTenantSchema(): void
{
    $schemaPath = base_path('tests/tenant_schema.sql');

    if (! File::exists($schemaPath)) {
        Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);
        return;
    }

    // Use mysql command line for reliable schema import
    $dbName = 'tenant_' . $this->tenant->id;
    $schemaContent = File::get($schemaPath);

    // Create a temporary file for the schema
    $tempFile = sys_get_temp_dir() . '/test_schema_' . uniqid() . '.sql';
    File::put($tempFile, $schemaContent);

    // Import using mysql command
    $command = sprintf(
        'mysql -u %s %s %s < %s 2>&1',
        config('database.connections.mysql.username'),
        config('database.connections.mysql.password') ? '-p' . config('database.connections.mysql.password') : '',
        $dbName,
        escapeshellarg($tempFile)
    );

    exec($command, $output, $returnCode);

    // Clean up temp file
    @unlink($tempFile);

    if ($returnCode !== 0) {
        // Fall back to migrations if import fails
        Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);
    }
}
```

### Option 2: Use SQLite for Testing

1. Update `.env.testing`:
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

2. This eliminates database setup time entirely

### Option 3: Use Database Transactions

Add to `TestCase.php`:
```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
}
```

## Running Tests

### Fast Unit Tests
```bash
./optimize-tests.sh unit
```

### All Tests (Parallel)
```bash
./optimize-tests.sh all
```

### Debug Specific Failures
```bash
php artisan test --stop-on-failure --filter="test_name"
```

## Expected Performance

| Configuration | Speed | Reliability |
|--------------|-------|-------------|
| Current (Migrations) | Slow (~40s/test) | High |
| Schema Dump (Fixed) | Fast (~0.5s/test) | High |
| SQLite Memory | Very Fast (~0.1s/test) | Medium |
| Transactions | Fast (~0.3s/test) | High |

## Monitoring Test Performance

```bash
# Profile slow tests
php artisan test --profile --compact

# Run with timing
time php artisan test --parallel --processes=4
```

## Known Remaining Issues

1. **Authorization Test**: `AlertSettingsTest` returns 403 - needs auth setup
2. **Performance**: Currently using migrations (slow) - implement one of the options above
3. **Parallel Safety**: Some tests may conflict when run in parallel

## Maintenance

1. **After Adding Migrations**: Regenerate schema dump
```bash
php artisan tenants:migrate --tenants=test-tenant-fixed
# Then export the schema to tests/tenant_schema.sql
```

2. **Regular Cleanup**: Clear test database monthly
```bash
mysql -e "DROP DATABASE IF EXISTS tenant_test-tenant-fixed"
```

3. **CI/CD**: Use parallel testing with optimal process count
```yaml
php artisan test --parallel --processes=8
```