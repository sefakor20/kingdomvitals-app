<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Models\PlatformInvoice;
use App\Models\PlatformPaymentReminder;
use App\Models\SuperAdminActivityLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OverdueInvoices extends Component
{
    public array $selectedInvoices = [];

    public bool $selectAll = false;

    #[Computed]
    public function overdueInvoices(): Collection
    {
        return PlatformInvoice::with(['tenant', 'reminders'])
            ->overdue()
            ->orderBy('due_date')
            ->get()
            ->map(function (PlatformInvoice $invoice) {
                $lastReminder = $invoice->reminders()->latest('sent_at')->first();

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'tenant' => $invoice->tenant,
                    'amount' => Number::currency((float) $invoice->balance_due, in: $invoice->currency),
                    'amountRaw' => (float) $invoice->balance_due,
                    'due_date' => $invoice->due_date->format('M d, Y'),
                    'days_overdue' => $invoice->daysOverdue(),
                    'last_reminder' => $lastReminder?->sent_at?->format('M d, Y'),
                    'last_reminder_type' => $lastReminder?->getTypeLabel(),
                    'reminder_count' => $invoice->reminders->count(),
                ];
            });
    }

    #[Computed]
    public function summary(): array
    {
        $invoices = $this->overdueInvoices;

        return [
            'total_count' => $invoices->count(),
            'total_amount' => Number::currency($invoices->sum('amountRaw'), in: 'GHS'),
            'over_30_days' => $invoices->filter(fn ($i) => $i['days_overdue'] >= 30)->count(),
            'over_14_days' => $invoices->filter(fn ($i) => $i['days_overdue'] >= 14 && $i['days_overdue'] < 30)->count(),
            'under_14_days' => $invoices->filter(fn ($i) => $i['days_overdue'] < 14)->count(),
        ];
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedInvoices = $this->overdueInvoices->pluck('id')->toArray();
        } else {
            $this->selectedInvoices = [];
        }
    }

    public function sendReminder(string $invoiceId): void
    {
        $invoice = PlatformInvoice::with('tenant')->findOrFail($invoiceId);
        $email = $invoice->tenant?->contact_email;

        if (! $email) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'No contact email found for this tenant.',
            ]);

            return;
        }

        $daysOverdue = $invoice->daysOverdue();
        $reminderType = match (true) {
            $daysOverdue >= 45 => PlatformPaymentReminder::TYPE_FINAL_NOTICE,
            $daysOverdue >= 30 => PlatformPaymentReminder::TYPE_OVERDUE_30,
            $daysOverdue >= 14 => PlatformPaymentReminder::TYPE_OVERDUE_14,
            default => PlatformPaymentReminder::TYPE_OVERDUE_7,
        };

        try {
            Mail::send('emails.platform.payment-reminder', [
                'invoice' => $invoice,
                'tenant' => $invoice->tenant,
                'reminderType' => $reminderType,
                'invoiceUrl' => route('superadmin.billing.invoices.show', $invoice),
            ], function ($message) use ($email, $invoice, $reminderType) {
                $subject = match ($reminderType) {
                    PlatformPaymentReminder::TYPE_FINAL_NOTICE => "Final Notice - Invoice {$invoice->invoice_number}",
                    PlatformPaymentReminder::TYPE_OVERDUE_30 => "Urgent: 30 Days Overdue - Invoice {$invoice->invoice_number}",
                    PlatformPaymentReminder::TYPE_OVERDUE_14 => "Urgent: Payment Overdue - Invoice {$invoice->invoice_number}",
                    default => "Payment Overdue - Invoice {$invoice->invoice_number}",
                };
                $message->to($email)->subject($subject);
            });

            PlatformPaymentReminder::create([
                'platform_invoice_id' => $invoice->id,
                'type' => $reminderType,
                'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
                'sent_at' => now(),
                'recipient_email' => $email,
            ]);

            SuperAdminActivityLog::log(
                superAdmin: Auth::guard('superadmin')->user(),
                action: 'send_payment_reminder',
                description: "Sent {$reminderType} reminder for invoice {$invoice->invoice_number}",
                targetType: PlatformInvoice::class,
                targetId: $invoice->id,
            );

            unset($this->overdueInvoices);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Reminder sent successfully.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to send reminder: '.$e->getMessage(),
            ]);
        }
    }

    public function sendBulkReminders(): void
    {
        if (empty($this->selectedInvoices)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'No invoices selected.',
            ]);

            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($this->selectedInvoices as $invoiceId) {
            try {
                $invoice = PlatformInvoice::with('tenant')->find($invoiceId);
                $email = $invoice?->tenant?->contact_email;

                if (! $email) {
                    $failed++;

                    continue;
                }

                $daysOverdue = $invoice->daysOverdue();
                $reminderType = match (true) {
                    $daysOverdue >= 45 => PlatformPaymentReminder::TYPE_FINAL_NOTICE,
                    $daysOverdue >= 30 => PlatformPaymentReminder::TYPE_OVERDUE_30,
                    $daysOverdue >= 14 => PlatformPaymentReminder::TYPE_OVERDUE_14,
                    default => PlatformPaymentReminder::TYPE_OVERDUE_7,
                };

                Mail::send('emails.platform.payment-reminder', [
                    'invoice' => $invoice,
                    'tenant' => $invoice->tenant,
                    'reminderType' => $reminderType,
                    'invoiceUrl' => route('superadmin.billing.invoices.show', $invoice),
                ], function ($message) use ($email, $invoice) {
                    $message->to($email)->subject("Payment Overdue - Invoice {$invoice->invoice_number}");
                });

                PlatformPaymentReminder::create([
                    'platform_invoice_id' => $invoice->id,
                    'type' => $reminderType,
                    'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
                    'sent_at' => now(),
                    'recipient_email' => $email,
                ]);

                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'send_bulk_reminders',
            description: "Sent bulk payment reminders: {$sent} sent, {$failed} failed",
            metadata: [
                'sent' => $sent,
                'failed' => $failed,
                'invoice_ids' => $this->selectedInvoices,
            ],
        );

        $this->selectedInvoices = [];
        $this->selectAll = false;
        unset($this->overdueInvoices);

        $this->dispatch('notification', [
            'type' => $failed > 0 ? 'warning' : 'success',
            'message' => "Reminders sent: {$sent} successful, {$failed} failed.",
        ]);
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.overdue-invoices')
            ->layout('components.layouts.superadmin.app');
    }
}
