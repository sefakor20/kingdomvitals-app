<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\CalculateClusterHealthJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class CalculateClusterHealthCommand extends Command
{
    protected $signature = 'ai:calculate-cluster-health
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}
                            {--no-notify : Disable health alert notifications}';

    protected $description = 'Calculate and update cluster health scores using AI analysis';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');
        $notify = ! $this->option('no-notify');

        $this->info('Calculating cluster health scores...');

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $sync, $notify);
            $this->info('Done!');

            return Command::SUCCESS;
        }

        // Process tenants
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $totalBranches = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $branches = Branch::all();

            foreach ($branches as $branch) {
                $this->processBranch($branch->id, $sync, $notify);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, bool $sync, bool $notify): void
    {
        $this->line("  - Dispatching cluster health calculation for branch {$branchId}");

        $job = new CalculateClusterHealthJob($branchId, 50, $notify);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
