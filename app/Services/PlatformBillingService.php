<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use App\Enums\TenantStatus;
use App\Mail\PlatformInvoiceMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformPayment;
use App\Models\PlatformPaymentReminder;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PlatformBillingService
{
    /**
     * Generate monthly invoices for all active tenants.
     *
     * @return Collection<int, PlatformInvoice>
     */
    public function generateMonthlyInvoices(): Collection
    {
        $invoices = collect();
        $billingPeriod = now()->format('F Y');

        $tenants = Tenant::whereIn('status', [TenantStatus::Active, TenantStatus::Trial])
            ->whereHas('subscriptionPlan')
            ->get();

        foreach ($tenants as $tenant) {
            // Skip if tenant has cancelled and their subscription has ended
            if ($tenant->hasCancellationExpired()) {
                continue;
            }

            // Skip if invoice already exists for this period
            $existingInvoice = PlatformInvoice::where('tenant_id', $tenant->id)
                ->where('billing_period', $billingPeriod)
                ->first();

            if ($existingInvoice) {
                continue;
            }

            try {
                $invoice = $this->generateInvoiceForTenant($tenant, BillingCycle::Monthly);
                $invoices->push($invoice);
            } catch (\Exception $e) {
                Log::error('Failed to generate invoice for tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $invoices;
    }

    /**
     * Generate an invoice for a specific tenant.
     */
    public function generateInvoiceForTenant(Tenant $tenant, BillingCycle $cycle): PlatformInvoice
    {
        $plan = $tenant->subscriptionPlan;

        if (! $plan) {
            throw new \InvalidArgumentException('Tenant does not have a subscription plan');
        }

        $price = $cycle === BillingCycle::Annual
            ? $plan->price_annual
            : $plan->price_monthly;

        $periodStart = $cycle === BillingCycle::Annual
            ? now()->startOfYear()
            : now()->startOfMonth();

        $periodEnd = $cycle === BillingCycle::Annual
            ? now()->endOfYear()
            : now()->endOfMonth();

        $billingPeriod = $cycle === BillingCycle::Annual
            ? now()->format('Y').' Annual'
            : now()->format('F Y');

        return DB::transaction(function () use ($tenant, $plan, $price, $periodStart, $periodEnd, $billingPeriod, $cycle) {
            $invoice = PlatformInvoice::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_period' => $billingPeriod,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => now()->addDays(14),
                'subtotal' => $price,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $price,
                'amount_paid' => 0,
                'balance_due' => $price,
                'status' => InvoiceStatus::Draft,
                'currency' => SystemSetting::get('base_currency', 'GHS'),
            ]);

            PlatformInvoiceItem::create([
                'platform_invoice_id' => $invoice->id,
                'description' => "{$plan->name} - {$cycle->label()} Subscription",
                'quantity' => 1,
                'unit_price' => $price,
                'total' => $price,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate an upgrade invoice for a tenant switching to a new plan.
     * The invoice is created in SENT status, ready for immediate payment.
     *
     * @param  array{days_remaining: int, days_used: int, days_in_period: int, old_plan_credit: float, new_plan_cost: float, amount_due: float, credit_generated: float, change_type: string}|null  $prorationData
     */
    public function generateUpgradeInvoice(
        Tenant $tenant,
        SubscriptionPlan $newPlan,
        BillingCycle $cycle,
        ?string $upgradeReason = null,
        ?array $prorationData = null
    ): PlatformInvoice {
        $fullPrice = $cycle === BillingCycle::Annual
            ? (float) $newPlan->price_annual
            : (float) $newPlan->price_monthly;

        $periodStart = now();
        $periodEnd = $cycle === BillingCycle::Annual
            ? now()->addYear()
            : now()->addMonth();

        $billingPeriod = $cycle === BillingCycle::Annual
            ? now()->format('Y').' Annual Upgrade'
            : now()->format('F Y').' Upgrade';

        // Calculate amounts with proration
        $prorationCredit = $prorationData['old_plan_credit'] ?? 0;
        $subtotal = $prorationData ? $prorationData['new_plan_cost'] : $fullPrice;
        $totalAmount = $prorationData ? $prorationData['amount_due'] : $fullPrice;
        $changeType = $prorationData['change_type'] ?? 'upgrade';

        return DB::transaction(function () use ($tenant, $newPlan, $fullPrice, $periodStart, $periodEnd, $billingPeriod, $cycle, $upgradeReason, $prorationData, $prorationCredit, $subtotal, $totalAmount, $changeType) {
            $invoice = PlatformInvoice::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $newPlan->id,
                'previous_plan_id' => $tenant->subscription_id,
                'billing_period' => $billingPeriod,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => now()->addDay(),
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'proration_credit' => $prorationCredit,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'balance_due' => $totalAmount,
                'status' => InvoiceStatus::Sent,
                'currency' => SystemSetting::get('base_currency', 'GHS'),
                'notes' => $upgradeReason,
                'change_type' => $changeType,
                'metadata' => [
                    'upgrade_type' => 'self_service',
                    'previous_plan_id' => $tenant->subscription_id,
                    'billing_cycle' => $cycle->value,
                    'proration' => $prorationData,
                ],
            ]);

            // Add credit line item if there's proration
            if ($prorationCredit > 0 && $tenant->subscriptionPlan) {
                PlatformInvoiceItem::create([
                    'platform_invoice_id' => $invoice->id,
                    'description' => "Credit for unused {$tenant->subscriptionPlan->name} plan ({$prorationData['days_remaining']} days)",
                    'quantity' => 1,
                    'unit_price' => -$prorationCredit,
                    'total' => -$prorationCredit,
                ]);
            }

            // Add the new plan line item
            $lineItemDescription = $prorationData
                ? "{$newPlan->name} - {$cycle->label()} Subscription ({$prorationData['days_remaining']} days prorated)"
                : "{$newPlan->name} - {$cycle->label()} Subscription (Upgrade)";

            PlatformInvoiceItem::create([
                'platform_invoice_id' => $invoice->id,
                'description' => $lineItemDescription,
                'quantity' => 1,
                'unit_price' => $prorationData ? $prorationData['new_plan_cost'] : $fullPrice,
                'total' => $prorationData ? $prorationData['new_plan_cost'] : $fullPrice,
            ]);

            return $invoice;
        });
    }

    /**
     * Record a payment for an invoice.
     *
     * @param  array{amount: float, payment_method: PlatformPaymentMethod, notes?: string, paystack_reference?: string, send_confirmation?: bool}  $data
     */
    public function recordPayment(PlatformInvoice $invoice, array $data): PlatformPayment
    {
        $payment = PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $data['amount'],
            'currency' => $invoice->currency,
            'payment_method' => $data['payment_method'],
            'status' => PlatformPaymentStatus::Successful,
            'notes' => $data['notes'] ?? null,
            'paystack_reference' => $data['paystack_reference'] ?? null,
            'paid_at' => now(),
        ]);

        $invoice->recordPayment($data['amount']);

        // Send confirmation email if requested (default: true)
        if ($data['send_confirmation'] ?? true) {
            $this->sendPaymentConfirmationEmail($payment);
        }

        return $payment;
    }

    /**
     * Apply a discount to an invoice.
     */
    public function applyDiscount(PlatformInvoice $invoice, float $amount, string $reason): void
    {
        if (! $invoice->status->canBeEdited() && $invoice->status !== InvoiceStatus::Sent) {
            throw new \InvalidArgumentException('Cannot apply discount to this invoice');
        }

        $newDiscount = (float) $invoice->discount_amount + $amount;
        $newTotal = (float) $invoice->subtotal - $newDiscount + (float) $invoice->tax_amount;
        $newBalance = $newTotal - (float) $invoice->amount_paid;

        $invoice->update([
            'discount_amount' => $newDiscount,
            'total_amount' => $newTotal,
            'balance_due' => max(0, $newBalance),
            'notes' => $invoice->notes."\n\nDiscount applied: ".number_format($amount, 2)." - {$reason}",
        ]);
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(PlatformInvoice $invoice, string $reason): void
    {
        if (! $invoice->status->canBeCancelled()) {
            throw new \InvalidArgumentException('This invoice cannot be cancelled');
        }

        $invoice->cancel($reason);
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(PlatformPayment $payment, ?float $amount = null): void
    {
        if (! $payment->isSuccessful()) {
            throw new \InvalidArgumentException('Can only refund successful payments');
        }

        $refundAmount = $amount ?? (float) $payment->amount;

        DB::transaction(function () use ($payment, $refundAmount): void {
            $payment->refund();

            $invoice = $payment->invoice;
            $newAmountPaid = (float) $invoice->amount_paid - $refundAmount;
            $newBalance = (float) $invoice->total_amount - $newAmountPaid;

            $invoice->update([
                'amount_paid' => max(0, $newAmountPaid),
                'balance_due' => $newBalance,
                'status' => InvoiceStatus::Refunded,
            ]);
        });
    }

    /**
     * Check and mark overdue invoices.
     */
    public function checkOverdueInvoices(): int
    {
        $count = 0;

        $invoices = PlatformInvoice::unpaid()
            ->where('due_date', '<', now())
            ->where('status', '!=', InvoiceStatus::Overdue)
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->markAsOverdue();
            $count++;
        }

        return $count;
    }

    /**
     * Get invoices that need reminders.
     *
     * @return Collection<int, array{invoice: PlatformInvoice, type: string}>
     */
    public function getInvoicesNeedingReminders(): Collection
    {
        $reminders = collect();

        // Upcoming due (3 days before)
        $upcomingInvoices = PlatformInvoice::where('status', InvoiceStatus::Sent)
            ->whereDate('due_date', now()->addDays(3))
            ->get();

        foreach ($upcomingInvoices as $invoice) {
            if (! $this->hasReminderOfType($invoice, PlatformPaymentReminder::TYPE_UPCOMING)) {
                $reminders->push(['invoice' => $invoice, 'type' => PlatformPaymentReminder::TYPE_UPCOMING]);
            }
        }

        // Overdue reminders
        $overdueInvoices = PlatformInvoice::overdue()->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $invoice->daysOverdue();

            if ($daysOverdue >= 7 && $daysOverdue < 14 && ! $this->hasReminderOfType($invoice, PlatformPaymentReminder::TYPE_OVERDUE_7)) {
                $reminders->push(['invoice' => $invoice, 'type' => PlatformPaymentReminder::TYPE_OVERDUE_7]);
            } elseif ($daysOverdue >= 14 && $daysOverdue < 30 && ! $this->hasReminderOfType($invoice, PlatformPaymentReminder::TYPE_OVERDUE_14)) {
                $reminders->push(['invoice' => $invoice, 'type' => PlatformPaymentReminder::TYPE_OVERDUE_14]);
            } elseif ($daysOverdue >= 30 && $daysOverdue < 45 && ! $this->hasReminderOfType($invoice, PlatformPaymentReminder::TYPE_OVERDUE_30)) {
                $reminders->push(['invoice' => $invoice, 'type' => PlatformPaymentReminder::TYPE_OVERDUE_30]);
            } elseif ($daysOverdue >= 45 && ! $this->hasReminderOfType($invoice, PlatformPaymentReminder::TYPE_FINAL_NOTICE)) {
                $reminders->push(['invoice' => $invoice, 'type' => PlatformPaymentReminder::TYPE_FINAL_NOTICE]);
            }
        }

        return $reminders;
    }

    /**
     * Record that a reminder was sent.
     */
    public function recordReminderSent(
        PlatformInvoice $invoice,
        string $type,
        string $channel,
        ?string $recipientEmail = null,
        ?string $recipientPhone = null
    ): PlatformPaymentReminder {
        return PlatformPaymentReminder::create([
            'platform_invoice_id' => $invoice->id,
            'type' => $type,
            'channel' => $channel,
            'sent_at' => now(),
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
        ]);
    }

    /**
     * Check if an invoice already has a reminder of a specific type.
     */
    protected function hasReminderOfType(PlatformInvoice $invoice, string $type): bool
    {
        return $invoice->reminders()->where('type', $type)->exists();
    }

    /**
     * Get billing statistics for dashboard.
     *
     * @return array{total_revenue_ytd: float, outstanding_balance: float, overdue_amount: float, paid_this_month: float}
     */
    public function getBillingStats(): array
    {
        $startOfYear = now()->startOfYear();
        $startOfMonth = now()->startOfMonth();

        return [
            'total_revenue_ytd' => (float) PlatformPayment::successful()
                ->where('paid_at', '>=', $startOfYear)
                ->sum('amount'),
            'outstanding_balance' => (float) PlatformInvoice::unpaid()
                ->sum('balance_due'),
            'overdue_amount' => (float) PlatformInvoice::overdue()
                ->sum('balance_due'),
            'paid_this_month' => (float) PlatformPayment::successful()
                ->where('paid_at', '>=', $startOfMonth)
                ->sum('amount'),
        ];
    }

    /**
     * Get monthly revenue data for the last 12 months.
     *
     * @return Collection<int, array{month: string, amount: float}>
     */
    public function getMonthlyRevenueData(): Collection
    {
        $data = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $amount = (float) PlatformPayment::successful()
                ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $data->push([
                'month' => $date->format('M Y'),
                'amount' => $amount,
            ]);
        }

        return $data;
    }

    /**
     * Send invoice email to tenant.
     */
    public function sendInvoiceEmail(PlatformInvoice $invoice): bool
    {
        $email = $invoice->tenant?->contact_email;

        if (empty($email)) {
            Log::warning('Cannot send invoice email: no contact email', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
            ]);

            return false;
        }

        try {
            Mail::to($email)->send(new PlatformInvoiceMail($invoice));

            $this->recordEmailSent($invoice, PlatformPaymentReminder::TYPE_INVOICE_SENT, $email);

            Log::info('Invoice email sent', [
                'invoice_id' => $invoice->id,
                'email' => $email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send payment confirmation email to tenant.
     */
    public function sendPaymentConfirmationEmail(PlatformPayment $payment, bool $attachInvoice = true): bool
    {
        $email = $payment->invoice->tenant?->contact_email;

        if (empty($email)) {
            Log::warning('Cannot send payment confirmation email: no contact email', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->platform_invoice_id,
            ]);

            return false;
        }

        try {
            Mail::to($email)->send(new PlatformPaymentReceivedMail($payment, $attachInvoice));

            $this->recordEmailSent($payment->invoice, PlatformPaymentReminder::TYPE_PAYMENT_RECEIVED, $email);

            Log::info('Payment confirmation email sent', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->platform_invoice_id,
                'email' => $email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record that an email was sent for an invoice.
     */
    public function recordEmailSent(
        PlatformInvoice $invoice,
        string $type,
        string $recipientEmail
    ): PlatformPaymentReminder {
        return PlatformPaymentReminder::create([
            'platform_invoice_id' => $invoice->id,
            'type' => $type,
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'sent_at' => now(),
            'recipient_email' => $recipientEmail,
        ]);
    }

    /**
     * Send invoice and mark as sent.
     * This combines the status update with email sending.
     */
    public function sendInvoice(PlatformInvoice $invoice): bool
    {
        if (! $invoice->status->canBeSent()) {
            return false;
        }

        $invoice->markAsSent();

        return $this->sendInvoiceEmail($invoice);
    }
}
