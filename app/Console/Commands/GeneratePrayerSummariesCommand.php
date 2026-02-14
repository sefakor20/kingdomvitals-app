<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\GeneratePrayerSummaryJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\AI\PrayerAnalysisService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GeneratePrayerSummariesCommand extends Command
{
    protected $signature = 'ai:generate-prayer-summaries
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--period=weekly : Period type (weekly or monthly)}
                            {--date= : Reference date for period calculation (defaults to now)}
                            {--overwrite : Overwrite existing summaries}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Generate AI-powered prayer request summaries for branches';

    public function handle(PrayerAnalysisService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Prayer analysis feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $periodType = $this->option('period');
        $overwrite = $this->option('overwrite');
        $sync = $this->option('sync');

        if (! in_array($periodType, ['weekly', 'monthly'])) {
            $this->error('Invalid period type. Use "weekly" or "monthly".');

            return Command::FAILURE;
        }

        // Calculate period dates
        $referenceDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::now();

        [$periodStart, $periodEnd] = $this->calculatePeriod($referenceDate, $periodType);

        $this->info("Generating {$periodType} prayer summaries...");
        $this->line("Period: {$periodStart->toDateString()} to {$periodEnd->toDateString()}");

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $periodType, $periodStart, $periodEnd, $overwrite, $sync);
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
                    $periodType,
                    $periodStart,
                    $periodEnd,
                    $overwrite,
                    $sync
                );
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    /**
     * Calculate period start and end dates.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function calculatePeriod(Carbon $referenceDate, string $periodType): array
    {
        if ($periodType === 'weekly') {
            // Previous week (Sunday to Saturday)
            $periodEnd = $referenceDate->copy()->previous(Carbon::SATURDAY);
            $periodStart = $periodEnd->copy()->subDays(6);
        } else {
            // Previous month
            $periodStart = $referenceDate->copy()->subMonth()->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
        }

        return [$periodStart, $periodEnd];
    }

    protected function processBranch(
        string $branchId,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        bool $overwrite,
        bool $sync
    ): void {
        $this->line("  - Generating {$periodType} summary for branch {$branchId}");

        $job = new GeneratePrayerSummaryJob(
            $branchId,
            $periodType,
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
            $overwrite
        );

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
