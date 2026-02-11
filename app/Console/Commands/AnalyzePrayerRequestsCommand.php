<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\AnalyzePrayerRequestsJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\PrayerAnalysisService;
use Illuminate\Console\Command;

class AnalyzePrayerRequestsCommand extends Command
{
    protected $signature = 'ai:analyze-prayers
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--reanalyze : Re-analyze all prayers, not just unanalyzed}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Analyze prayer requests for urgency and priority scoring';

    public function handle(PrayerAnalysisService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Prayer analysis feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $reanalyze = $this->option('reanalyze');
        $sync = $this->option('sync');

        $this->info('Analyzing prayer requests...');

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $reanalyze, $sync);
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
                $this->processBranch($branch->id, $reanalyze, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, bool $reanalyze, bool $sync): void
    {
        $this->line("  - Analyzing prayers for branch {$branchId}");

        if ($sync) {
            dispatch_sync(new AnalyzePrayerRequestsJob($branchId, $reanalyze));
        } else {
            dispatch(new AnalyzePrayerRequestsJob($branchId, $reanalyze));
        }
    }
}
