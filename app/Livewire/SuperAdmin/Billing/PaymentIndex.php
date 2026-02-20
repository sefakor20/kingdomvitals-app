<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasReportExport;
use App\Models\PlatformPayment;
use App\Models\SuperAdminActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentIndex extends Component
{
    use HasFilterableQuery;
    use HasReportExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $method = '';

    #[Url]
    public string $sortBy = 'paid_at';

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

    public function updatedMethod(): void
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
        $this->reset(['search', 'status', 'method']);
        $this->resetPage();
    }

    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        $query = PlatformPayment::with(['invoice', 'tenant']);

        // Search includes tenant relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $query->where(function ($q): void {
                $q->where('payment_reference', 'like', "%{$this->search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$this->search}%"));
            });
        }

        $this->applyEnumFilter($query, 'status', 'status');
        $this->applyEnumFilter($query, 'method', 'payment_method');

        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->status)) {
            return true;
        }
        return $this->isFilterActive($this->method);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(PlatformPaymentStatus::cases())
            ->mapWithKeys(fn (PlatformPaymentStatus $status): array => [$status->value => $status->label()])
            ->toArray();
    }

    #[Computed]
    public function methodOptions(): array
    {
        return collect(PlatformPaymentMethod::cases())
            ->mapWithKeys(fn (PlatformPaymentMethod $method): array => [$method->value => $method->label()])
            ->toArray();
    }

    public function exportCsv(): StreamedResponse
    {
        $query = PlatformPayment::with(['invoice', 'tenant']);

        // Search includes tenant relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $query->where(function ($q): void {
                $q->where('payment_reference', 'like', "%{$this->search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$this->search}%"));
            });
        }

        $this->applyEnumFilter($query, 'status', 'status');
        $this->applyEnumFilter($query, 'method', 'payment_method');

        $payments = $query->orderBy($this->sortBy, $this->sortDirection)->get();

        $data = $payments->map(fn (PlatformPayment $payment): array => [
            'date' => $payment->paid_at?->format('Y-m-d H:i:s') ?? $payment->created_at->format('Y-m-d H:i:s'),
            'reference' => $payment->payment_reference,
            'tenant' => $payment->tenant?->name ?? 'Unknown',
            'invoice' => $payment->invoice?->invoice_number ?? 'N/A',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'method' => $payment->payment_method->value,
            'status' => $payment->status->value,
            'paystack_ref' => $payment->paystack_reference ?? '',
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
            'Paystack Ref',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_payments',
            description: 'Exported payments to CSV',
            metadata: ['count' => $payments->count()],
        );

        return $this->exportToCsv($data, $headers, 'payments-'.now()->format('Y-m-d').'.csv');
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.payment-index')
            ->layout('components.layouts.superadmin.app');
    }
}
