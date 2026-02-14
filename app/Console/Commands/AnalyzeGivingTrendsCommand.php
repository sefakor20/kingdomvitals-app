<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AI\AnalyzeGivingTrendsJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\AI\GivingTrendService;
use Illuminate\Console\Command;

class AnalyzeGivingTrendsCommand extends Command
{
    protected $signature = 'ai:analyze-giving-trends
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--member= : Specific member ID to process}
                            {--months=12 : Number of months of history to analyze}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Analyze giving trends for members to identify major donors, growth patterns, and at-risk givers';

    public function handle(GivingTrendService $service): int
    {
        if (! config('ai.features.giving_trends.enabled', true)) {
            $this->warn('Giving trends feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $memberId = $this->option('member');
        $historyMonths = (int) $this->option('months');
        $sync = $this->option('sync');

        if ($historyMonths < 1 || $historyMonths > 36) {
            $this->error('Months must be between 1 and 36.');

            return Command::FAILURE;
        }

        $this->info("Analyzing giving trends ({$historyMonths} months of history)...");

        // If a specific member is provided, process just that
        if ($memberId) {
            return $this->processMember($service, $memberId, $historyMonths);
        }

        // If a specific branch is provided, process just that
        if ($branchId) {
            $this->processBranch($branchId, $historyMonths, $sync);
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
                $this->processBranch($branch->id, $historyMonths, $sync);
                $totalBranches++;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalBranches} branch(es).");

        return Command::SUCCESS;
    }

    protected function processBranch(string $branchId, int $historyMonths, bool $sync): void
    {
        $this->line("  - Analyzing giving trends for branch {$branchId}");

        $job = new AnalyzeGivingTrendsJob($branchId, $historyMonths);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }

    protected function processMember(GivingTrendService $service, string $memberId, int $historyMonths): int
    {
        $member = Member::find($memberId);

        if (! $member) {
            $this->error("Member not found: {$memberId}");

            return Command::FAILURE;
        }

        $this->line("Analyzing giving trends for {$member->fullName()}...");

        $trend = $service->analyzeForMember($member, $historyMonths);
        $service->updateMemberGivingData($member, $trend);

        $this->info('Analysis complete:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Consistency Score', $trend->consistencyScore.'%'],
                ['Growth Rate', $trend->formattedGrowthRate()],
                ['Total Given', number_format($trend->totalGiven, 2)],
                ['Donation Count', $trend->donationCount],
                ['Donations/Month', number_format($trend->donationsPerMonth, 2)],
                ['Average Gift', number_format($trend->averageGift, 2)],
                ['Largest Gift', number_format($trend->largestGift, 2)],
                ['Donor Tier', $trend->tierLabel()],
                ['Trend', $trend->trendLabel()],
                ['Days Since Last', $trend->daysSinceLastDonation],
                ['Preferred Type', $trend->preferredTypeLabel() ?? 'N/A'],
                ['Preferred Method', $trend->preferredMethodLabel() ?? 'N/A'],
                ['Confidence', $trend->confidenceScore.'%'],
            ]
        );

        return Command::SUCCESS;
    }
}
