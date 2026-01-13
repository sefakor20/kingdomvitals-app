<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use Illuminate\Console\Command;

class LinkDonationsToMembersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:link-members {--tenant= : Specific tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link orphaned donations to members by matching donor_email to member email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return self::FAILURE;
        }

        $totalLinked = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name}");

            tenancy()->initialize($tenant);

            $linked = $this->linkDonationsForTenant();
            $totalLinked += $linked;

            $this->info("  Linked {$linked} donations to members.");

            tenancy()->end();
        }

        $this->newLine();
        $this->info("Total donations linked: {$totalLinked}");

        return self::SUCCESS;
    }

    private function linkDonationsForTenant(): int
    {
        $linked = 0;

        Donation::whereNull('member_id')
            ->whereNotNull('donor_email')
            ->chunk(100, function ($donations) use (&$linked): void {
                foreach ($donations as $donation) {
                    $member = Member::where('email', $donation->donor_email)
                        ->where('primary_branch_id', $donation->branch_id)
                        ->first();

                    if ($member) {
                        $donation->update(['member_id' => $member->id]);
                        $linked++;
                    }
                }
            });

        return $linked;
    }
}
