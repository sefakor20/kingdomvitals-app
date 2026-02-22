<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Enums\InvoiceStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasReportExport;
use App\Models\PlatformInvoice;
use App\Models\SuperAdminActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceIndex extends Component
{
    use HasFilterableQuery;
    use HasReportExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'issue_date';

    #[Url]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $query = PlatformInvoice::with(['tenant', 'subscriptionPlan']);

        // Search includes tenant relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $query->where(function ($q): void {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"));
            });
        }

        $this->applyEnumFilter($query, 'status', 'status');

        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }

        return $this->isFilterActive($this->status);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(InvoiceStatus::cases())
            ->mapWithKeys(fn (InvoiceStatus $status): array => [$status->value => $status->label()])
            ->toArray();
    }

    public function sendInvoice(string $invoiceId): void
    {
        $invoice = PlatformInvoice::findOrFail($invoiceId);

        if (! $invoice->status->canBeSent()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'This invoice cannot be sent.',
            ]);

            return;
        }

        $invoice->markAsSent();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'send_invoice',
            description: "Sent invoice {$invoice->invoice_number} to {$invoice->tenant?->name}",
            tenant: $invoice->tenant,
            metadata: ['invoice_id' => $invoice->id],
        );

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Invoice sent successfully.',
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $query = PlatformInvoice::with(['tenant', 'subscriptionPlan']);

        // Search includes tenant relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $query->where(function ($q): void {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"));
            });
        }

        $this->applyEnumFilter($query, 'status', 'status');

        $invoices = $query->orderBy($this->sortBy, $this->sortDirection)->get();

        $data = $invoices->map(fn (PlatformInvoice $invoice): array => [
            'invoice_number' => $invoice->invoice_number,
            'tenant' => $invoice->tenant?->name ?? 'Unknown',
            'plan' => $invoice->subscriptionPlan?->name ?? 'N/A',
            'billing_period' => $invoice->billing_period,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'total' => $invoice->total_amount,
            'paid' => $invoice->amount_paid,
            'balance' => $invoice->balance_due,
            'status' => $invoice->status->value,
        ]);

        $headers = [
            'Invoice #',
            'Tenant',
            'Plan',
            'Billing Period',
            'Issue Date',
            'Due Date',
            'Total',
            'Paid',
            'Balance',
            'Status',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_invoices',
            description: 'Exported invoices to CSV',
            metadata: ['count' => $invoices->count()],
        );

        return $this->exportToCsv($data, $headers, 'invoices-'.now()->format('Y-m-d').'.csv');
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.invoice-index')
            ->layout('components.layouts.superadmin.app');
    }
}
