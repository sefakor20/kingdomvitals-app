<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\CalculateDonorChurnScoresJob;
use App\Jobs\AI\CalculateVisitorConversionScoresJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class AiRecalculateScoresCommand extends Command
{
    protected $signature = 'ai:recalculate-scores
                            {--type=all : Type of scores to recalculate (conversion, churn, or all)}
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Recalculate AI prediction scores for visitors and members';

    public function handle(): int
    {
        $type = $this->option('type');
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $sync = $this->option('sync');

        if (! in_array($type, ['conversion', 'churn', 'all'])) {
            $this->error('Invalid type. Must be: conversion, churn, or all');

            return Command::FAILURE;
        }

        $this->info("Recalculating {$type} scores...");

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $type, $sync);
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
                $this->processBranch($branch->id, $type, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, string $type, bool $sync): void
    {
        if (in_array($type, ['conversion', 'all'])) {
            $this->line("  - Dispatching visitor conversion scores for branch {$branchId}");

            if ($sync) {
                dispatch_sync(new CalculateVisitorConversionScoresJob($branchId));
            } else {
                dispatch(new CalculateVisitorConversionScoresJob($branchId));
            }
        }

        if (in_array($type, ['churn', 'all'])) {
            $this->line("  - Dispatching donor churn scores for branch {$branchId}");

            if ($sync) {
                dispatch_sync(new CalculateDonorChurnScoresJob($branchId));
            } else {
                dispatch(new CalculateDonorChurnScoresJob($branchId));
            }
        }
    }
}
