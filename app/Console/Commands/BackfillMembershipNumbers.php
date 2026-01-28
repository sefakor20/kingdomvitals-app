<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Member;
use Illuminate\Console\Command;

class BackfillMembershipNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:backfill-numbers {--tenant= : Specific tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign membership numbers to existing members who do not have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return self::FAILURE;
        }

        $totalUpdated = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name} ({$tenant->id})");

            tenancy()->initialize($tenant);

            $updated = $this->backfillForTenant();
            $totalUpdated += $updated;

            $this->info("  - Updated {$updated} members");

            tenancy()->end();
        }

        $this->newLine();
        $this->info("Total members updated: {$totalUpdated}");

        return self::SUCCESS;
    }

    /**
     * Backfill membership numbers for the current tenant.
     */
    protected function backfillForTenant(): int
    {
        $members = Member::withTrashed()
            ->whereNull('membership_number')
            ->orderBy('created_at')
            ->get();

        if ($members->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($members as $member) {
            $member->membership_number = Member::generateMembershipNumber();
            $member->saveQuietly(); // Bypass observers to avoid activity logging
            $count++;
        }

        return $count;
    }
}
