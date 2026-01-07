<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Enums\BillingCycle;
use App\Enums\TenantStatus;
use App\Models\PlatformInvoice;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Services\PlatformBillingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InvoiceCreate extends Component
{
    public string $tenantId = '';

    public string $billingCycle = 'monthly';

    public string $dueDate = '';

    public string $notes = '';

    /** @var array<int, array{description: string, quantity: int, unit_price: float}> */
    public array $customItems = [];

    public bool $useCustomItems = false;

    protected function rules(): array
    {
        return [
            'tenantId' => 'required|exists:tenants,id',
            'billingCycle' => 'required|in:monthly,annual',
            'dueDate' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'customItems' => 'array',
            'customItems.*.description' => 'required_if:useCustomItems,true|string|max:255',
            'customItems.*.quantity' => 'required_if:useCustomItems,true|integer|min:1',
            'customItems.*.unit_price' => 'required_if:useCustomItems,true|numeric|min:0.01',
        ];
    }

    public function mount(): void
    {
        $this->dueDate = now()->addDays(14)->format('Y-m-d');
    }

    #[Computed]
    public function tenants(): Collection
    {
        return Tenant::whereIn('status', [TenantStatus::Active->value, TenantStatus::Trial->value])
            ->whereHas('subscriptionPlan')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function selectedTenant(): ?Tenant
    {
        if (! $this->tenantId) {
            return null;
        }

        return Tenant::with('subscriptionPlan')->find($this->tenantId);
    }

    #[Computed]
    public function estimatedAmount(): float
    {
        if ($this->useCustomItems) {
            return collect($this->customItems)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
        }

        $tenant = $this->selectedTenant;
        if (! $tenant?->subscriptionPlan) {
            return 0;
        }

        $cycle = BillingCycle::from($this->billingCycle);

        return $cycle === BillingCycle::Annual
            ? (float) $tenant->subscriptionPlan->price_annual
            : (float) $tenant->subscriptionPlan->price_monthly;
    }

    public function addCustomItem(): void
    {
        $this->customItems[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
        ];
    }

    public function removeCustomItem(int $index): void
    {
        unset($this->customItems[$index]);
        $this->customItems = array_values($this->customItems);
    }

    public function createInvoice(): void
    {
        $this->validate();

        $tenant = Tenant::with('subscriptionPlan')->findOrFail($this->tenantId);
        $billingService = app(PlatformBillingService::class);
        $cycle = BillingCycle::from($this->billingCycle);

        if ($this->useCustomItems && ! empty($this->customItems)) {
            // Create custom invoice
            $subtotal = collect($this->customItems)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            $invoice = PlatformInvoice::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $tenant->subscriptionPlan?->id,
                'billing_period' => 'Custom Invoice - '.now()->format('F Y'),
                'period_start' => now(),
                'period_end' => now()->addMonth(),
                'issue_date' => now(),
                'due_date' => $this->dueDate,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'amount_paid' => 0,
                'balance_due' => $subtotal,
                'notes' => $this->notes ?: null,
            ]);

            foreach ($this->customItems as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        } else {
            // Generate standard invoice
            $invoice = $billingService->generateInvoiceForTenant($tenant, $cycle);

            if ($this->notes) {
                $invoice->update(['notes' => $this->notes]);
            }

            if ($this->dueDate !== now()->addDays(14)->format('Y-m-d')) {
                $invoice->update(['due_date' => $this->dueDate]);
            }
        }

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'create_invoice',
            description: "Created invoice {$invoice->invoice_number} for {$tenant->name}",
            tenant: $tenant,
            metadata: [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'cycle' => $this->billingCycle,
                'custom' => $this->useCustomItems,
            ],
        );

        session()->flash('success', "Invoice {$invoice->invoice_number} created successfully.");

        $this->redirect(route('superadmin.billing.invoices.show', $invoice), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.invoice-create')
            ->layout('components.layouts.superadmin.app');
    }
}
