<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\DetectLifecycleStagesJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class DetectLifecycleStagesCommand extends Command
{
    protected $signature = 'ai:detect-lifecycle-stages
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}
                            {--no-notify : Disable transition notifications}';

    protected $description = 'Detect and update member lifecycle stages using AI analysis';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');
        $notify = ! $this->option('no-notify');

        $this->info('Detecting member lifecycle stages...');

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
        $this->line("  - Dispatching lifecycle detection for branch {$branchId}");

        $job = new DetectLifecycleStagesJob($branchId, 50, $notify);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
