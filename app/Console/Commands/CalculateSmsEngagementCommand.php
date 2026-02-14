<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\CalculateSmsEngagementScoresJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\SmsCampaignOptimizationService;
use Illuminate\Console\Command;

class CalculateSmsEngagementCommand extends Command
{
    protected $signature = 'ai:calculate-sms-engagement
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Calculate and update SMS engagement scores for members';

    public function handle(SmsCampaignOptimizationService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('SMS campaign optimization feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');

        $this->info('Calculating SMS engagement scores...');

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
        $this->line("  - Calculating engagement scores for branch {$branchId}");

        if ($sync) {
            dispatch_sync(new CalculateSmsEngagementScoresJob($branchId));
        } else {
            dispatch(new CalculateSmsEngagementScoresJob($branchId));
        }
    }
}
