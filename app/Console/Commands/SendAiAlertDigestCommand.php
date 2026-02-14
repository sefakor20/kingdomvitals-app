<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\SendAiAlertDigestJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Console\Command;

class SendAiAlertDigestCommand extends Command
{
    protected $signature = 'ai:send-alert-digest
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--hours=24 : Number of hours to look back for alerts}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Send daily AI alert digest emails to branch administrators';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $hours = (int) $this->option('hours');
        $sync = $this->option('sync');

        $this->info("Sending AI alert digests (last {$hours} hours)...");

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $hours, $sync);
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
                $this->processBranch($branch->id, $hours, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Sent digests for {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, int $hours, bool $sync): void
    {
        $this->line("  - Dispatching alert digest for branch {$branchId}");

        $job = new SendAiAlertDigestJob($branchId, $hours);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
