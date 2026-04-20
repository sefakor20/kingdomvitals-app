<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoicesCommand extends Command
{
    protected $signature = 'billing:check-overdue';

    protected $description = 'Check and mark overdue invoices';

    public function handle(PlatformBillingService $billingService): int
    {
        $this->info('Checking for overdue invoices...');

        try {
            $overdueCount = $billingService->checkOverdueInvoices();

            $this->info("Marked {$overdueCount} invoices as overdue");

            if ($overdueCount > 0) {
                Log::info('Overdue invoices marked', ['count' => $overdueCount]);
            }

            $suspendedCount = $billingService->suspendTenantsWithOverdueInvoices(30);

            if ($suspendedCount > 0) {
                $this->warn("Suspended {$suspendedCount} tenant(s) with invoices overdue 30+ days");
                Log::info('Tenants suspended for overdue invoices', ['count' => $suspendedCount]);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to check overdue invoices: {$e->getMessage()}");
            Log::error('Overdue invoice check failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
