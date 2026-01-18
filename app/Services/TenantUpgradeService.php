<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\PlatformPaymentMethod;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantUpgradeService
{
    public function __construct(
        private PlatformBillingService $billingService,
        private PlatformPaystackService $paystackService,
        private PlanAccessService $planAccessService,
        private ProrationService $prorationService,
    ) {}

    /**
     * Initiate the upgrade process: create invoice and initialize Paystack payment.
     *
     * @return array{success: bool, invoice?: PlatformInvoice, payment_url?: string, reference?: string, error?: string, proration?: array}
     */
    public function initiateUpgrade(
        Tenant $tenant,
        SubscriptionPlan $newPlan,
        BillingCycle $cycle,
        string $email,
        string $callbackUrl
    ): array {
        if (! $newPlan->is_active) {
            return ['success' => false, 'error' => 'Selected plan is not available.'];
        }

        if ($tenant->subscription_id === $newPlan->id) {
            return ['success' => false, 'error' => 'You are already on this plan.'];
        }

        try {
            // Calculate proration if tenant has an active billing period
            $prorationData = null;
            if ($this->prorationService->shouldApplyProration($tenant)) {
                $prorationData = $this->prorationService->calculatePlanChange($tenant, $newPlan, $cycle);
            }

            $invoice = $this->billingService->generateUpgradeInvoice(
                tenant: $tenant,
                newPlan: $newPlan,
                cycle: $cycle,
                upgradeReason: 'Self-service upgrade from tenant portal',
                prorationData: $prorationData
            );

            $amount = PlatformPaystackService::toKobo((float) $invoice->total_amount);

            $paystackResult = $this->paystackService->initializeTransaction([
                'email' => $email,
                'amount' => $amount,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'plan_id' => $newPlan->id,
                    'billing_cycle' => $cycle->value,
                    'type' => 'plan_upgrade',
                ],
            ]);

            if (! $paystackResult['success']) {
                $invoice->cancel('Payment initialization failed');

                return ['success' => false, 'error' => $paystackResult['error'] ?? 'Payment initialization failed.'];
            }

            $invoice->update([
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'paystack_reference' => $paystackResult['reference'],
                ]),
            ]);

            return [
                'success' => true,
                'invoice' => $invoice,
                'payment_url' => $paystackResult['data']['authorization_url'],
                'reference' => $paystackResult['reference'],
            ];
        } catch (\Exception $e) {
            Log::error('Tenant upgrade initiation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Failed to initiate upgrade. Please try again.'];
        }
    }

    /**
     * Complete the upgrade after successful payment verification.
     *
     * @return array{success: bool, tenant?: Tenant, plan?: SubscriptionPlan, invoice?: PlatformInvoice, already_processed?: bool, error?: string}
     */
    public function completeUpgrade(string $paystackReference): array
    {
        $verifyResult = $this->paystackService->verifyTransaction($paystackReference);

        if (! $verifyResult['success']) {
            return ['success' => false, 'error' => 'Payment verification failed.'];
        }

        $invoice = PlatformInvoice::whereJsonContains('metadata->paystack_reference', $paystackReference)->first();

        if (! $invoice) {
            Log::error('Invoice not found for paystack reference', ['reference' => $paystackReference]);

            return ['success' => false, 'error' => 'Invoice not found.'];
        }

        if ($invoice->status->value === 'paid') {
            return ['success' => true, 'already_processed' => true, 'tenant' => $invoice->tenant];
        }

        $tenant = $invoice->tenant;
        $newPlan = $invoice->subscriptionPlan;

        try {
            return DB::transaction(function () use ($invoice, $tenant, $newPlan, $paystackReference) {
                $previousPlanId = $tenant->subscription_id;

                $this->billingService->recordPayment($invoice, [
                    'amount' => (float) $invoice->total_amount,
                    'payment_method' => PlatformPaymentMethod::Paystack,
                    'paystack_reference' => $paystackReference,
                    'notes' => 'Self-service plan upgrade payment',
                ]);

                // Update subscription and billing period
                $billingCycle = BillingCycle::from($invoice->metadata['billing_cycle'] ?? 'monthly');
                $tenant->update([
                    'subscription_id' => $newPlan->id,
                    'billing_cycle' => $billingCycle->value,
                    'current_period_start' => $invoice->period_start,
                    'current_period_end' => $invoice->period_end,
                ]);

                // Apply any credit generated from proration (for downgrades)
                $creditGenerated = $invoice->metadata['proration']['credit_generated'] ?? 0;
                if ($creditGenerated > 0) {
                    $tenant->applyCredit((float) $creditGenerated);
                }

                $this->planAccessService->clearCache();

                Log::info('Tenant plan upgrade completed', [
                    'tenant_id' => $tenant->id,
                    'previous_plan_id' => $previousPlanId,
                    'new_plan_id' => $newPlan->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'billing_cycle' => $billingCycle->value,
                    'period_start' => $invoice->period_start,
                    'period_end' => $invoice->period_end,
                ]);

                return [
                    'success' => true,
                    'tenant' => $tenant,
                    'plan' => $newPlan,
                    'invoice' => $invoice,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Tenant upgrade completion failed', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Failed to complete upgrade. Please contact support.'];
        }
    }

    /**
     * Check if a tenant can upgrade to a specific plan.
     */
    public function canUpgradeTo(Tenant $tenant, SubscriptionPlan $plan): bool
    {
        if (! $plan->is_active) {
            return false;
        }

        return $tenant->subscription_id !== $plan->id;
    }
}
