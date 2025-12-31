<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

trait TenantTestCase
{
    use RefreshDatabase;

    protected ?Tenant $tenant = null;

    protected function setUpTenancy(): void
    {
        // Create a test tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Church',
        ]);

        $this->tenant->domains()->create([
            'domain' => 'test.localhost',
        ]);

        // Initialize tenancy
        tenancy()->initialize($this->tenant);

        // Run tenant migrations
        Artisan::call('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
        ]);
    }

    protected function tearDownTenancy(): void
    {
        if ($this->tenant) {
            tenancy()->end();

            // Delete the tenant database
            $this->tenant->delete();
        }
    }
}
