<?php

declare(strict_types=1);

namespace App\Console\Commands\AI;

use App\Jobs\AI\PredictPledgeFulfillmentJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\PledgePredictionService;
use Illuminate\Console\Command;

class PredictPledgeFulfillmentCommand extends Command
{
    protected $signature = 'ai:predict-pledge-fulfillment
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Predict pledge fulfillment probability and identify at-risk pledges';

    public function handle(PledgePredictionService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Pledge prediction feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');

        $this->info('Predicting pledge fulfillment...');

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
        $this->line("  - Predicting pledge fulfillment for branch {$branchId}");

        $job = new PredictPledgeFulfillmentJob($branchId);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
