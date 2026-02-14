<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiAlertType;
use App\Jobs\AI\ProcessAiAlertsJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class ProcessAiAlertsCommand extends Command
{
    protected $signature = 'ai:process-alerts
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--type= : Specific alert type to process (churn_risk, attendance_anomaly, lifecycle_change, critical_prayer, cluster_health, household_disengagement)}
                            {--sync : Run synchronously instead of dispatching jobs}
                            {--no-notify : Disable notifications for created alerts}';

    protected $description = 'Process and generate AI alerts for all configured alert types';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $typeValue = $this->option('type');
        $sync = $this->option('sync');
        $notify = ! $this->option('no-notify');

        // Validate alert type if provided
        $alertType = null;
        if ($typeValue) {
            $alertType = AiAlertType::tryFrom($typeValue);
            if ($alertType === null) {
                $validTypes = implode(', ', array_map(fn ($t) => $t->value, AiAlertType::cases()));
                $this->error("Invalid alert type: {$typeValue}. Valid types: {$validTypes}");

                return Command::FAILURE;
            }
        }

        $this->info('Processing AI alerts...');

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $alertType, $sync, $notify);
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
                $this->processBranch($branch->id, $alertType, $sync, $notify);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(
        string $branchId,
        ?AiAlertType $alertType,
        bool $sync,
        bool $notify
    ): void {
        $typeLabel = $alertType?->label() ?? 'all';
        $this->line("  - Dispatching {$typeLabel} alerts for branch {$branchId}");

        $job = new ProcessAiAlertsJob($branchId, $alertType, $notify);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
