<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateTenantSchemaForTesting extends Command
{
    protected $signature = 'test:generate-tenant-schema';

    protected $description = 'Generate a SQL schema dump for tenant database testing';

    public function handle(): int
    {
        $this->info('Generating tenant schema for testing...');

        // Create a temporary tenant to generate the schema
        $plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'test-unlimited'],
            [
                'name' => 'Test Unlimited',
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

        $this->info('Creating temporary tenant...');
        $tenant = Tenant::create([
            'name' => 'Schema Generator Tenant',
            'subscription_id' => $plan->id,
        ]);

        $tenantDbName = 'tenant_'.$tenant->id;

        try {
            // Run migrations
            $this->info('Running tenant migrations...');
            Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);

            // Initialize tenancy to switch to tenant database
            tenancy()->initialize($tenant);

            // Generate schema using SHOW CREATE TABLE
            $this->info('Generating schema from database...');
            $schema = $this->generateSchemaFromDatabase();

            $schemaPath = base_path('tests/tenant_schema.sql');
            File::put($schemaPath, $schema);

            $this->info("Schema saved to: {$schemaPath}");
            $this->info('File size: '.number_format(filesize($schemaPath)).' bytes');

            // End tenancy before cleanup
            tenancy()->end();

        } finally {
            // Clean up: delete the temporary tenant and its database
            $this->info('Cleaning up temporary tenant...');

            // Make sure tenancy is ended
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            $tenant->delete();

            // Drop the database
            DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `{$tenantDbName}`");
        }

        $this->info('Done! You can now run tests with faster schema restoration.');

        return self::SUCCESS;
    }

    /**
     * Generate SQL schema from current database using SHOW CREATE TABLE.
     */
    protected function generateSchemaFromDatabase(): string
    {
        $lines = [];

        // Header
        $lines[] = '-- Tenant Database Schema for Testing';
        $lines[] = '-- Generated: '.date('Y-m-d H:i:s');
        $lines[] = '-- This file is auto-generated. Do not edit manually.';
        $lines[] = '-- Run: php artisan test:generate-tenant-schema to regenerate.';
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;';
        $lines[] = 'SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;';
        $lines[] = 'SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = '';

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;

            // Get CREATE TABLE statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createSql = $createTable[0]->{'Create Table'};

            // Add DROP TABLE IF EXISTS
            $lines[] = "DROP TABLE IF EXISTS `{$tableName}`;";
            $lines[] = $createSql.';';
            $lines[] = '';
        }

        // Footer
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = 'SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;';
        $lines[] = 'SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;';
        $lines[] = 'SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;';
        $lines[] = '';

        return implode("\n", $lines);
    }
}
