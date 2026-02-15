<?php

namespace Tests;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

trait TenantTestCase
{
    protected ?Tenant $tenant = null;

    /**
     * Fixed tenant ID for testing - allows database reuse.
     */
    protected const TEST_TENANT_ID = 'test-tenant-fixed';

    /**
     * Set up a test tenant using a shared, pre-migrated database.
     * Uses a fixed tenant ID so the same database can be reused across tests.
     */
    protected function setUpTestTenant(): void
    {
        // Create subscription plan
        $plan = $this->getOrCreateTestPlan();

        $tenantDbName = 'tenant_'.self::TEST_TENANT_ID;

        // Check if the test tenant database already exists and has schema
        $dbExists = $this->tenantDatabaseHasSchema($tenantDbName);

        // Always create/find tenant record (may be inside transaction)
        // Check if tenant already exists first
        $this->tenant = Tenant::find(self::TEST_TENANT_ID);

        if (! $this->tenant) {
            // Create new tenant with fixed ID, without triggering tenancy events
            // This prevents TenantDatabaseAlreadyExistsException when reusing database
            Tenant::withoutEvents(function () use ($plan): void {
                $this->tenant = new Tenant;
                $this->tenant->id = self::TEST_TENANT_ID;
                $this->tenant->name = 'Test Church';
                $this->tenant->subscription_id = $plan->id;
                $this->tenant->save();
            });
        }

        // Ensure domain exists
        if (! $this->tenant->domains()->where('domain', 'test.localhost')->exists()) {
            $this->tenant->domains()->create(['domain' => 'test.localhost']);
        }

        if (! $dbExists) {
            // First time: create database and load schema
            DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$tenantDbName}`");

            // Initialize tenancy to switch connection
            tenancy()->initialize($this->tenant);

            // Load schema (fast) or run migrations (slow fallback)
            $this->loadTenantSchema();
        } else {
            // Database exists with schema: just initialize and truncate
            tenancy()->initialize($this->tenant);

            // Truncate all tables to clear data from previous test
            $this->truncateTenantTables();
        }

        $this->tenant->markOnboardingComplete();

        // Configure routing
        config(['app.url' => 'http://test.localhost']);
        url()->forceRootUrl('http://test.localhost');
        $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);
    }

    /**
     * Check if the tenant database exists and has the schema loaded.
     */
    protected function tenantDatabaseHasSchema(string $dbName): bool
    {
        try {
            $result = DB::connection('mysql')->select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$dbName]
            );

            if (empty($result)) {
                return false;
            }

            // Check if a known table exists (migrations table is always present)
            $tables = DB::connection('mysql')->select(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'migrations'",
                [$dbName]
            );

            return ! empty($tables);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load the tenant schema from the pre-generated SQL dump.
     */
    protected function loadTenantSchema(): void
    {
        $schemaPath = base_path('tests/tenant_schema.sql');

        if (! File::exists($schemaPath)) {
            // Fall back to migrations if schema doesn't exist
            Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

            return;
        }

        // Load schema using PHP
        $schema = File::get($schemaPath);

        // Remove comments and split by semicolons
        $schema = preg_replace('/--.*$/m', '', $schema);
        $statements = preg_split('/;\s*\n/', $schema);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '' && $statement !== '0') {
                try {
                    DB::unprepared($statement);
                } catch (\Exception $e) {
                    // Continue on error (e.g., table already exists)
                }
            }
        }
    }

    /**
     * Truncate all tenant tables to reset data between tests.
     * This is MUCH faster than dropping and recreating the database.
     */
    protected function truncateTenantTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            if ($tableName !== 'migrations') {
                DB::table($tableName)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Get or create a subscription plan for testing with all modules enabled.
     */
    protected function getOrCreateTestPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::firstOrCreate(
            ['slug' => 'test-unlimited'],
            [
                'name' => 'Test Unlimited',
                'description' => 'Unlimited plan for testing',
                'price_monthly' => 0,
                'price_annual' => 0,
                'max_members' => 999999,
                'max_branches' => 999999,
                'storage_quota_gb' => 999999,
                'sms_credits_monthly' => 999999,
                'max_households' => 999999,
                'max_clusters' => 999999,
                'max_visitors' => 999999,
                'max_equipment' => 999999,
                'enabled_modules' => null,
                'features' => [],
                'is_active' => true,
                'is_default' => true,
                'display_order' => 0,
            ]
        );
    }

    /**
     * Tear down the test tenant.
     */
    protected function tearDownTestTenant(): void
    {
        tenancy()->end();
        $this->tenant = null;
    }
}
