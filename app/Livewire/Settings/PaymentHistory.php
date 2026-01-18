<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PaymentHistory extends Component
{
    /**
     * Get all invoices for the current tenant.
     *
     * @return Collection<int, PlatformInvoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        $tenantId = tenant()?->id;

        if (! $tenantId) {
            return collect();
        }

        return PlatformInvoice::forTenant($tenantId)
            ->with('subscriptionPlan')
            ->orderByDesc('issue_date')
            ->get();
    }

    /**
     * Get all payments for the current tenant.
     *
     * @return Collection<int, PlatformPayment>
     */
    #[Computed]
    public function payments(): Collection
    {
        $tenantId = tenant()?->id;

        if (! $tenantId) {
            return collect();
        }

        return PlatformPayment::forTenant($tenantId)
            ->with('invoice')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if there is any billing history.
     */
    #[Computed]
    public function hasBillingHistory(): bool
    {
        return $this->invoices->isNotEmpty() || $this->payments->isNotEmpty();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.settings.payment-history');
    }
}
