<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\GenerateFinancialForecastJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\FinancialForecastService;
use Illuminate\Console\Command;

class GenerateFinancialForecastCommand extends Command
{
    protected $signature = 'ai:forecast-financial
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--type=monthly : Forecast type (monthly or quarterly)}
                            {--periods=4 : Number of periods ahead to forecast}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Generate financial forecasts for branches using AI-powered prediction';

    public function handle(FinancialForecastService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Financial forecast feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $forecastType = $this->option('type');
        $periodsAhead = (int) $this->option('periods');
        $sync = $this->option('sync');

        if (! in_array($forecastType, ['monthly', 'quarterly'])) {
            $this->error('Invalid forecast type. Use "monthly" or "quarterly".');

            return Command::FAILURE;
        }

        if ($periodsAhead < 1 || $periodsAhead > 12) {
            $this->error('Periods must be between 1 and 12.');

            return Command::FAILURE;
        }

        $this->info("Generating {$forecastType} financial forecasts ({$periodsAhead} periods ahead)...");

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $forecastType, $periodsAhead, $sync);
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
                $this->processBranch(
                    $branch->id,
                    $forecastType,
                    $periodsAhead,
                    $sync
                );
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(
        string $branchId,
        string $forecastType,
        int $periodsAhead,
        bool $sync
    ): void {
        $this->line("  - Generating {$forecastType} forecast for branch {$branchId}");

        $job = new GenerateFinancialForecastJob(
            $branchId,
            $forecastType,
            $periodsAhead
        );

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
