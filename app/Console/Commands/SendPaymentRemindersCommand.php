<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformPaymentReminder;
use App\Services\PlatformBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'billing:send-reminders
                            {--dry-run : Show what would be sent without sending}';

    protected $description = 'Send payment reminders for upcoming and overdue invoices';

    public function handle(PlatformBillingService $billingService): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No emails will be sent');
        }

        $this->info('Checking for invoices needing reminders...');

        try {
            $reminders = $billingService->getInvoicesNeedingReminders();

            if ($reminders->isEmpty()) {
                $this->info('No reminders needed');

                return Command::SUCCESS;
            }

            $this->info("Found {$reminders->count()} reminders to send");

            $sentCount = 0;
            $failedCount = 0;

            foreach ($reminders as $reminder) {
                $invoice = $reminder['invoice'];
                $type = $reminder['type'];
                $email = $invoice->tenant->contact_email;

                if (empty($email)) {
                    $this->warn("  Skipping {$invoice->invoice_number}: No contact email");
                    $failedCount++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("  Would send {$type} reminder to {$email} for {$invoice->invoice_number}");
                    $sentCount++;

                    continue;
                }

                try {
                    // Send reminder email
                    Mail::send('emails.platform.payment-reminder', [
                        'invoice' => $invoice,
                        'tenant' => $invoice->tenant,
                        'reminderType' => $type,
                        'invoiceUrl' => route('superadmin.billing.invoices.show', $invoice),
                    ], function ($message) use ($email, $invoice, $type): void {
                        $subject = match ($type) {
                            PlatformPaymentReminder::TYPE_UPCOMING => "Payment Due Soon - Invoice {$invoice->invoice_number}",
                            PlatformPaymentReminder::TYPE_OVERDUE_7 => "Payment Overdue - Invoice {$invoice->invoice_number}",
                            PlatformPaymentReminder::TYPE_OVERDUE_14 => "Urgent: Payment Overdue - Invoice {$invoice->invoice_number}",
                            PlatformPaymentReminder::TYPE_OVERDUE_30 => "Urgent: 30 Days Overdue - Invoice {$invoice->invoice_number}",
                            PlatformPaymentReminder::TYPE_FINAL_NOTICE => "Final Notice - Invoice {$invoice->invoice_number}",
                            default => "Payment Reminder - Invoice {$invoice->invoice_number}",
                        };

                        $message->to($email)->subject($subject);
                    });

                    // Record that reminder was sent
                    $billingService->recordReminderSent(
                        $invoice,
                        $type,
                        PlatformPaymentReminder::CHANNEL_EMAIL,
                        $email
                    );

                    $this->line("  Sent {$type} reminder to {$email} for {$invoice->invoice_number}");
                    $sentCount++;
                } catch (\Exception $e) {
                    $this->error("  Failed to send reminder for {$invoice->invoice_number}: {$e->getMessage()}");
                    $failedCount++;
                    Log::error('Failed to send payment reminder', [
                        'invoice_id' => $invoice->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
            $this->info("Reminders sent: {$sentCount}, Failed: {$failedCount}");

            Log::info('Payment reminders sent', [
                'sent' => $sentCount,
                'failed' => $failedCount,
            ]);

            return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send reminders: {$e->getMessage()}");
            Log::error('Payment reminder command failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
