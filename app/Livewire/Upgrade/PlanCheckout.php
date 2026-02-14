<?php

declare(strict_types=1);

namespace App\Livewire\Upgrade;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Services\PlatformPaystackService;
use App\Services\TenantUpgradeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PlanCheckout extends Component
{
    public SubscriptionPlan $plan;

    #[Url]
    public string $billingCycle = 'monthly';

    public ?string $errorMessage = null;

    public bool $isProcessing = false;

    public bool $showSuccess = false;

    public function mount(SubscriptionPlan $plan, ?string $cycle = null): void
    {
        $this->plan = $plan;

        if ($cycle && in_array($cycle, ['monthly', 'annual'])) {
            $this->billingCycle = $cycle;
        }

        if (! $plan->is_active) {
            $this->redirectRoute('plans.index', navigate: true);

            return;
        }

        if (tenant()?->subscription_id === $plan->id) {
            session()->flash('info', 'You are already on this plan.');
            $this->redirectRoute('plans.index', navigate: true);
        }
    }

    /**
     * Get the platform's base currency for billing.
     */
    #[Computed]
    public function currency(): Currency
    {
        return Currency::fromString(SystemSetting::get('base_currency', 'GHS'));
    }

    /**
     * Get the selected price based on billing cycle.
     */
    #[Computed]
    public function selectedPrice(): float
    {
        return $this->billingCycle === 'annual'
            ? (float) $this->plan->price_annual
            : (float) $this->plan->price_monthly;
    }

    /**
     * Get the billing cycle as enum.
     */
    #[Computed]
    public function billingCycleEnum(): BillingCycle
    {
        return $this->billingCycle === 'annual'
            ? BillingCycle::Annual
            : BillingCycle::Monthly;
    }

    /**
     * Get the Paystack public key.
     */
    #[Computed]
    public function paystackPublicKey(): string
    {
        return app(PlatformPaystackService::class)->getPublicKey();
    }

    /**
     * Check if Paystack is configured.
     */
    #[Computed]
    public function paystackConfigured(): bool
    {
        return app(PlatformPaystackService::class)->isConfigured();
    }

    /**
     * Get annual savings percentage.
     */
    #[Computed]
    public function annualSavings(): float
    {
        return $this->plan->getAnnualSavingsPercent();
    }

    /**
     * Initiate the payment process.
     */
    public function initiatePayment(): void
    {
        if (! $this->paystackConfigured) {
            $this->errorMessage = 'Payment system is not configured. Please contact support.';

            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = null;

        $tenant = tenant();
        $user = auth()->user();

        $upgradeService = app(TenantUpgradeService::class);

        $result = $upgradeService->initiateUpgrade(
            tenant: $tenant,
            newPlan: $this->plan,
            cycle: $this->billingCycleEnum,
            email: $user->email ?? $tenant->contact_email ?? '',
            callbackUrl: route('plans.index')
        );

        if (! $result['success']) {
            $this->isProcessing = false;
            $this->errorMessage = $result['error'];

            return;
        }

        $this->dispatch('open-paystack', [
            'key' => $this->paystackPublicKey,
            'email' => $user->email ?? $tenant->contact_email ?? '',
            'amount' => PlatformPaystackService::toKobo($this->selectedPrice),
            'currency' => $this->currency->code(),
            'reference' => $result['reference'],
            'metadata' => [
                'invoice_id' => $result['invoice']->id,
                'tenant_id' => $tenant->id,
                'plan_id' => $this->plan->id,
            ],
        ]);
    }

    /**
     * Handle successful payment from Paystack.
     */
    public function handlePaymentSuccess(string $reference): void
    {
        $upgradeService = app(TenantUpgradeService::class);

        $result = $upgradeService->completeUpgrade($reference);

        if (! $result['success']) {
            $this->isProcessing = false;
            $this->errorMessage = $result['error'];

            return;
        }

        $this->showSuccess = true;
        $this->isProcessing = false;

        $this->dispatch('upgrade-complete');
    }

    /**
     * Handle payment popup closed without completing.
     */
    public function handlePaymentClosed(): void
    {
        $this->isProcessing = false;
        $this->errorMessage = 'Payment was cancelled. You can try again when ready.';
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.upgrade.plan-checkout');
    }
}
