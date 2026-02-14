<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\GenerateAttendanceForecastsJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class ForecastAttendanceCommand extends Command
{
    protected $signature = 'ai:forecast-attendance
                            {--weeks=4 : Number of weeks ahead to forecast}
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Generate attendance forecasts for services';

    public function handle(): int
    {
        $weeks = (int) $this->option('weeks');
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');

        if ($weeks < 1 || $weeks > 12) {
            $this->error('Weeks must be between 1 and 12');

            return Command::FAILURE;
        }

        $this->info("Generating attendance forecasts for the next {$weeks} week(s)...");

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $weeks, $sync);
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
                $this->processBranch($branch->id, $weeks, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, int $weeks, bool $sync): void
    {
        $this->line("  - Generating forecasts for branch {$branchId}");

        if ($sync) {
            dispatch_sync(new GenerateAttendanceForecastsJob($branchId, $weeks));
        } else {
            dispatch(new GenerateAttendanceForecastsJob($branchId, $weeks));
        }
    }
}
