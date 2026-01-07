<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-invoices
                            {--dry-run : Show what would be generated without creating invoices}';

    protected $description = 'Generate monthly invoices for all active tenants';

    public function handle(PlatformBillingService $billingService): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No invoices will be created');
        }

        $this->info('Generating monthly invoices...');

        try {
            $invoices = $billingService->generateMonthlyInvoices();

            if ($dryRun) {
                $this->info("Would generate {$invoices->count()} invoices");

                return Command::SUCCESS;
            }

            $this->info("Generated {$invoices->count()} invoices");

            foreach ($invoices as $invoice) {
                $this->line("  - {$invoice->invoice_number}: {$invoice->tenant->name} ({$invoice->currency} {$invoice->total_amount})");
            }

            Log::info('Monthly invoice generation completed', [
                'count' => $invoices->count(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate invoices: {$e->getMessage()}");
            Log::error('Monthly invoice generation failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
