<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Enums\InvoiceStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\SuperAdminActivityLog;
use App\Services\PlatformBillingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingDashboard extends Component
{
    use HasReportExport;

    #[Computed]
    public function billingStats(): array
    {
        $billingService = app(PlatformBillingService::class);
        $stats = $billingService->getBillingStats();

        return [
            'totalRevenueYtd' => Number::currency($stats['total_revenue_ytd'], in: 'GHS'),
            'totalRevenueYtdRaw' => $stats['total_revenue_ytd'],
            'outstandingBalance' => Number::currency($stats['outstanding_balance'], in: 'GHS'),
            'outstandingBalanceRaw' => $stats['outstanding_balance'],
            'overdueAmount' => Number::currency($stats['overdue_amount'], in: 'GHS'),
            'overdueAmountRaw' => $stats['overdue_amount'],
            'paidThisMonth' => Number::currency($stats['paid_this_month'], in: 'GHS'),
            'paidThisMonthRaw' => $stats['paid_this_month'],
        ];
    }

    #[Computed]
    public function invoiceCounts(): array
    {
        return [
            'draft' => PlatformInvoice::draft()->count(),
            'sent' => PlatformInvoice::sent()->count(),
            'overdue' => PlatformInvoice::overdue()->count(),
            'paid' => PlatformInvoice::where('status', InvoiceStatus::Paid)
                ->whereMonth('paid_at', now()->month)
                ->count(),
        ];
    }

    #[Computed]
    public function recentPayments(): Collection
    {
        return PlatformPayment::with(['invoice', 'tenant'])
            ->successful()
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get()
            ->map(fn (PlatformPayment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->payment_reference,
                'tenant' => $payment->tenant?->name ?? 'Unknown',
                'invoice' => $payment->invoice?->invoice_number,
                'amount' => Number::currency((float) $payment->amount, in: $payment->currency),
                'method' => $payment->payment_method->label(),
                'paid_at' => $payment->paid_at?->format('M d, Y g:i A'),
            ]);
    }

    #[Computed]
    public function overdueInvoices(): Collection
    {
        return PlatformInvoice::with('tenant')
            ->overdue()
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (PlatformInvoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'tenant' => $invoice->tenant?->name ?? 'Unknown',
                'amount' => Number::currency((float) $invoice->balance_due, in: $invoice->currency),
                'due_date' => $invoice->due_date->format('M d, Y'),
                'days_overdue' => $invoice->daysOverdue(),
            ]);
    }

    #[Computed]
    public function monthlyRevenueData(): array
    {
        $billingService = app(PlatformBillingService::class);
        $data = $billingService->getMonthlyRevenueData();

        return [
            'labels' => $data->pluck('month')->toArray(),
            'amounts' => $data->pluck('amount')->toArray(),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $payments = PlatformPayment::with(['invoice', 'tenant'])
            ->successful()
            ->whereYear('paid_at', now()->year)
            ->orderByDesc('paid_at')
            ->get();

        $data = $payments->map(fn (PlatformPayment $payment): array => [
            'date' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'reference' => $payment->payment_reference,
            'tenant' => $payment->tenant?->name ?? 'Unknown',
            'invoice' => $payment->invoice?->invoice_number,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'method' => $payment->payment_method->value,
            'status' => $payment->status->value,
        ]);

        $headers = [
            'Date',
            'Reference',
            'Tenant',
            'Invoice',
            'Amount',
            'Currency',
            'Method',
            'Status',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_billing_report',
            description: 'Exported billing report to CSV',
            metadata: ['payment_count' => $payments->count()],
        );

        return $this->exportToCsv($data, $headers, 'billing-report-'.now()->format('Y-m-d').'.csv');
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.billing-dashboard')
            ->layout('components.layouts.superadmin.app');
    }
}
