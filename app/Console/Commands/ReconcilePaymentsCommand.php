<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlatformPaymentStatus;
use App\Models\PlatformPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'billing:reconcile-payments
                            {--days=7 : Number of days to look back}';

    protected $description = 'Reconcile pending payments and flag discrepancies';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $this->info("Reconciling payments from the last {$days} days...");

        try {
            // Find pending payments that are older than expected
            $stalePendingPayments = PlatformPayment::pending()
                ->where('created_at', '<', now()->subHours(24))
                ->where('created_at', '>=', $startDate)
                ->get();

            if ($stalePendingPayments->isNotEmpty()) {
                $this->warn("Found {$stalePendingPayments->count()} stale pending payments:");

                foreach ($stalePendingPayments as $payment) {
                    $this->line("  - {$payment->payment_reference}: {$payment->currency} {$payment->amount} ({$payment->created_at->diffForHumans()})");
                }

                Log::warning('Stale pending payments found', [
                    'count' => $stalePendingPayments->count(),
                    'payment_ids' => $stalePendingPayments->pluck('id')->toArray(),
                ]);
            }

            // Find payments with Paystack references that need verification
            $paystackPayments = PlatformPayment::where('status', PlatformPaymentStatus::Pending)
                ->whereNotNull('paystack_reference')
                ->where('created_at', '>=', $startDate)
                ->get();

            if ($paystackPayments->isNotEmpty()) {
                $this->info("Found {$paystackPayments->count()} Paystack payments to verify");
                // Note: Actual Paystack verification would be implemented here
                // This is a placeholder for the reconciliation logic
            }

            // Summary of invoices with payment discrepancies
            $discrepancies = PlatformPayment::selectRaw('platform_invoice_id, SUM(amount) as total_paid')
                ->where('status', PlatformPaymentStatus::Successful)
                ->where('created_at', '>=', $startDate)
                ->groupBy('platform_invoice_id')
                ->with('invoice')
                ->get()
                ->filter(function ($payment) {
                    $invoice = $payment->invoice;

                    return $invoice && abs((float) $invoice->amount_paid - (float) $payment->total_paid) > 0.01;
                });

            if ($discrepancies->isNotEmpty()) {
                $this->error("Found {$discrepancies->count()} invoices with payment discrepancies:");

                foreach ($discrepancies as $payment) {
                    $invoice = $payment->invoice;
                    $this->line("  - {$invoice->invoice_number}: Recorded {$invoice->amount_paid}, Calculated {$payment->total_paid}");
                }

                Log::error('Payment discrepancies found', [
                    'count' => $discrepancies->count(),
                    'invoice_ids' => $discrepancies->pluck('platform_invoice_id')->toArray(),
                ]);
            }

            $this->newLine();
            $this->info('Reconciliation complete');
            $this->line("  Stale pending: {$stalePendingPayments->count()}");
            $this->line("  Discrepancies: {$discrepancies->count()}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Reconciliation failed: {$e->getMessage()}");
            Log::error('Payment reconciliation failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
