<?php

namespace App\Jobs;

use Illuminate\Support\Facades\File;
use Stancl\Tenancy\Contracts\Tenant;

class CreateTenantStorageDirectories
{
    protected Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        $suffixBase = config('tenancy.filesystem.suffix_base', 'tenant');
        $tenantStoragePath = storage_path().'/'.$suffixBase.$this->tenant->getTenantKey();

        $directories = [
            $tenantStoragePath.'/app',
            $tenantStoragePath.'/app/public',
            $tenantStoragePath.'/framework',
            $tenantStoragePath.'/framework/cache',
            $tenantStoragePath.'/framework/cache/data',
            $tenantStoragePath.'/framework/sessions',
            $tenantStoragePath.'/framework/views',
            $tenantStoragePath.'/logs',
        ];

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }
}
