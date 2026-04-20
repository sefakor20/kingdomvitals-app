<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendOverdueTenantsCommand extends Command
{
    protected $signature = 'subscriptions:suspend-overdue
                            {--days=30 : Number of days overdue before suspending}
                            {--dry-run : Show which tenants would be suspended without making changes}';

    protected $description = 'Suspend tenants whose invoices have been overdue for the specified number of days';

    public function handle(PlatformBillingService $billingService): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No tenants will be suspended');
        }

        $this->info("Checking for tenants with invoices overdue {$days}+ days...");

        try {
            if ($dryRun) {
                $count = $billingService->suspendTenantsWithOverdueInvoices($days, true);
                $this->info("Would suspend {$count} tenant(s)");
            } else {
                $count = $billingService->suspendTenantsWithOverdueInvoices($days);

                if ($count > 0) {
                    $this->warn("Suspended {$count} tenant(s) with invoices overdue {$days}+ days");
                    Log::info('Tenants suspended for overdue invoices', ['count' => $count, 'threshold_days' => $days]);
                } else {
                    $this->info('No tenants to suspend.');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to suspend overdue tenants: {$e->getMessage()}");
            Log::error('Tenant suspension for overdue invoices failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
