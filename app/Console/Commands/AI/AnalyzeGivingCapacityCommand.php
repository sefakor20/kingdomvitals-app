<?php

declare(strict_types=1);

namespace App\Console\Commands\AI;

use App\Jobs\AI\AnalyzeGivingCapacityJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\GivingCapacityService;
use Illuminate\Console\Command;

class AnalyzeGivingCapacityCommand extends Command
{
    protected $signature = 'ai:analyze-giving-capacity
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Analyze giving capacity for members to identify underdeveloped donors with high potential';

    public function handle(GivingCapacityService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Giving capacity feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');

        $this->info('Analyzing giving capacity...');

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $sync);
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
                $this->processBranch($branch->id, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, bool $sync): void
    {
        $this->line("  - Analyzing giving capacity for branch {$branchId}");

        $job = new AnalyzeGivingCapacityJob($branchId);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
