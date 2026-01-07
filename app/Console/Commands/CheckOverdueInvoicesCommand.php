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
            $count = $billingService->checkOverdueInvoices();

            $this->info("Marked {$count} invoices as overdue");

            if ($count > 0) {
                Log::info('Overdue invoices marked', ['count' => $count]);
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
