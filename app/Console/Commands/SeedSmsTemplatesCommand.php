<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\SmsTemplateSeeder;
use Illuminate\Console\Command;

class SeedSmsTemplatesCommand extends Command
{
    protected $signature = 'sms:seed-templates
                            {--tenant=* : Specific tenant ID(s) to process}';

    protected $description = 'Seed default SMS templates for tenant branches';

    public function __construct(
        protected SmsTemplateSeeder $templateSeeder
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantIds = $this->option('tenant');

        $tenants = empty($tenantIds)
            ? Tenant::all()
            : Tenant::whereIn('id', $tenantIds)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $this->info('Seeding default SMS templates...');

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name}");

            tenancy()->initialize($tenant);

            $branches = Branch::all();

            foreach ($branches as $branch) {
                $result = $this->templateSeeder->seedForBranch($branch);

                if ($result['created'] > 0) {
                    $this->line("  - Branch '{$branch->name}': created {$result['created']} templates");
                }

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
            }

            tenancy()->end();
        }

        $this->newLine();
        $this->info("Done! Created {$totalCreated} templates, skipped {$totalSkipped} (already exist).");

        return Command::SUCCESS;
    }
}
