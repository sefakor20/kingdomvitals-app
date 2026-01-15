<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant;
use App\Services\PlanAccessService;

class TenantObserver
{
    /**
     * Handle the Tenant "updated" event.
     */
    public function updated(Tenant $tenant): void
    {
        // Clear plan cache when subscription changes
        if ($tenant->isDirty('subscription_id')) {
            app(PlanAccessService::class)->clearCache();
        }
    }
}
