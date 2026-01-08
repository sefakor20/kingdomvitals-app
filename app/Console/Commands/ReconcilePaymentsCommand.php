<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlatformPaymentStatus;
use App\Models\PlatformPayment;
use App\Services\PlatformPaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'billing:reconcile-payments
                            {--days=7 : Number of days to look back}
                            {--dry-run : Preview without making changes}';

    protected $description = 'Reconcile pending payments and flag discrepancies';

    public function handle(PlatformPaystackService $paystackService): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $startDate = now()->subDays($days);

        $this->info("Reconciling payments from the last {$days} days...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

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

            $verified = 0;
            $failed = 0;
            $errors = 0;

            if ($paystackPayments->isNotEmpty()) {
                $this->info("Found {$paystackPayments->count()} Paystack payments to verify");

                if (! $paystackService->isConfigured()) {
                    $this->warn('  Platform Paystack credentials not configured - skipping verification');
                    $this->line('  Configure Paystack keys in Super Admin > Settings > Integration Defaults');
                } else {
                    foreach ($paystackPayments as $payment) {
                        $this->line("  Verifying {$payment->payment_reference}...");

                        try {
                            $result = $paystackService->verifyTransaction($payment->paystack_reference);

                            if ($result['success']) {
                                if (! $dryRun) {
                                    $payment->markAsSuccessful();
                                }
                                $verified++;
                                $this->info('    Verified successfully');

                                Log::info('Paystack payment verified during reconciliation', [
                                    'payment_id' => $payment->id,
                                    'reference' => $payment->paystack_reference,
                                    'amount' => $payment->amount,
                                ]);
                            } else {
                                $transactionStatus = $result['data']['status'] ?? 'unknown';

                                if (in_array($transactionStatus, ['failed', 'abandoned', 'reversed'])) {
                                    if (! $dryRun) {
                                        $payment->markAsFailed();
                                    }
                                    $failed++;
                                    $this->warn("    Failed: {$transactionStatus}");

                                    Log::warning('Paystack payment failed verification', [
                                        'payment_id' => $payment->id,
                                        'reference' => $payment->paystack_reference,
                                        'status' => $transactionStatus,
                                    ]);
                                } else {
                                    $this->line("    Status: {$transactionStatus} (still pending)");
                                }
                            }
                        } catch (\Exception $e) {
                            $errors++;
                            $this->error("    Error: {$e->getMessage()}");

                            Log::error('Paystack verification error during reconciliation', [
                                'payment_id' => $payment->id,
                                'reference' => $payment->paystack_reference,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
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
            $this->line("  Paystack verified: {$verified}");
            $this->line("  Paystack failed: {$failed}");
            $this->line("  Paystack errors: {$errors}");
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
