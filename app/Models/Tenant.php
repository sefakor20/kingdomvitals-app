<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'status',
        'trial_ends_at',
        'suspended_at',
        'suspension_reason',
        'subscription_id',
        'cancelled_at',
        'cancellation_reason',
        'subscription_ends_at',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'account_credit',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'account_credit' => 'decimal:2',
            'status' => TenantStatus::class,
        ];
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'contact_email',
            'contact_phone',
            'address',
            'status',
            'trial_ends_at',
            'suspended_at',
            'suspension_reason',
            'subscription_id',
            'cancelled_at',
            'cancellation_reason',
            'subscription_ends_at',
            'billing_cycle',
            'current_period_start',
            'current_period_end',
            'account_credit',
        ];
    }

    /**
     * Get the subscription plan for this tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_id');
    }

    /**
     * Check if tenant is in trial period
     */
    public function isInTrial(): bool
    {
        return $this->status === TenantStatus::Trial &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Trial || $this->status === TenantStatus::Active;
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    /**
     * Suspend the tenant with a reason.
     */
    public function suspend(string $reason): void
    {
        $this->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Reactivate a suspended or inactive tenant.
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Cancel the subscription with a reason.
     * Access continues until the end of the current billing period.
     */
    public function cancelSubscription(string $reason): void
    {
        $this->update([
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'subscription_ends_at' => now()->endOfMonth(),
        ]);
    }

    /**
     * Check if the subscription has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Check if the tenant is in the cancellation grace period.
     * (Cancelled but still has access until subscription_ends_at)
     */
    public function isInCancellationGracePeriod(): bool
    {
        return $this->isCancelled()
            && $this->subscription_ends_at
            && $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if the cancelled subscription has expired.
     */
    public function hasCancellationExpired(): bool
    {
        return $this->isCancelled()
            && $this->subscription_ends_at
            && $this->subscription_ends_at->isPast();
    }

    /**
     * Reactivate a cancelled subscription (before expiration).
     */
    public function reactivateSubscription(): void
    {
        $this->update([
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Get days remaining until subscription ends.
     */
    public function subscriptionDaysRemaining(): ?int
    {
        if (! $this->isCancelled() || ! $this->subscription_ends_at) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->subscription_ends_at, false));
    }

    /**
     * Get days remaining in trial.
     */
    public function trialDaysRemaining(): ?int
    {
        if (! $this->isInTrial() || ! $this->trial_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    // ============================================
    // BILLING PERIOD METHODS
    // ============================================

    /**
     * Get the billing cycle as an enum.
     */
    public function getBillingCycle(): ?BillingCycle
    {
        $cycle = $this->getAttribute('billing_cycle');

        return $cycle ? BillingCycle::from($cycle) : null;
    }

    /**
     * Check if tenant is in an active billing period.
     */
    public function isInActiveBillingPeriod(): bool
    {
        return $this->current_period_end && $this->current_period_end->isFuture();
    }

    /**
     * Get days remaining in the current billing period.
     */
    public function getDaysRemainingInPeriod(): int
    {
        if (! $this->current_period_end) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Apply credit to the tenant's account.
     */
    public function applyCredit(float $amount): void
    {
        $this->increment('account_credit', $amount);
    }

    /**
     * Use credit from the tenant's account.
     * Returns the amount of credit actually used.
     */
    public function useCredit(float $amount): float
    {
        $available = min((float) $this->account_credit, $amount);
        $this->decrement('account_credit', $available);

        return $available;
    }

    /**
     * Set the billing period for the tenant.
     */
    public function setBillingPeriod(BillingCycle $cycle, \Carbon\Carbon $periodStart, \Carbon\Carbon $periodEnd): void
    {
        $this->update([
            'billing_cycle' => $cycle->value,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
        ]);
    }

    /**
     * Get platform invoices for this tenant.
     */
    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class, 'tenant_id');
    }

    /**
     * Get platform payments for this tenant.
     */
    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class, 'tenant_id');
    }

    /**
     * Get the onboarding data from the data JSON column.
     *
     * @return array{completed: bool, completed_at: string|null, current_step: int, steps: array, branch_id: string|null}
     */
    public function getOnboardingData(): array
    {
        $default = [
            'completed' => false,
            'completed_at' => null,
            'current_step' => 1,
            'steps' => [
                'organization' => ['completed' => false, 'skipped' => false],
                'team' => ['completed' => false, 'skipped' => false],
                'integrations' => ['completed' => false, 'skipped' => false],
                'services' => ['completed' => false, 'skipped' => false],
            ],
            'branch_id' => null,
        ];

        return array_merge($default, $this->getAttribute('onboarding') ?? []);
    }

    /**
     * Update the onboarding data in the data JSON column.
     */
    public function setOnboardingData(array $data): void
    {
        $current = $this->getOnboardingData();
        $this->setAttribute('onboarding', array_merge($current, $data));
        $this->save();
    }

    /**
     * Check if tenant has completed onboarding.
     */
    public function isOnboardingComplete(): bool
    {
        return $this->getOnboardingData()['completed'] === true;
    }

    /**
     * Check if tenant needs to complete onboarding.
     */
    public function needsOnboarding(): bool
    {
        return ! $this->isOnboardingComplete();
    }

    /**
     * Get the current onboarding step (1-5).
     */
    public function getCurrentOnboardingStep(): int
    {
        return $this->getOnboardingData()['current_step'];
    }

    /**
     * Set the current onboarding step.
     */
    public function setCurrentOnboardingStep(int $step): void
    {
        $this->setOnboardingData(['current_step' => $step]);
    }

    /**
     * Mark a specific onboarding step as completed.
     */
    public function completeOnboardingStep(string $step): void
    {
        $data = $this->getOnboardingData();
        $data['steps'][$step] = ['completed' => true, 'skipped' => false];
        $this->setOnboardingData($data);
    }

    /**
     * Mark a specific onboarding step as skipped.
     */
    public function skipOnboardingStep(string $step): void
    {
        $data = $this->getOnboardingData();
        $data['steps'][$step] = ['completed' => false, 'skipped' => true];
        $this->setOnboardingData($data);
    }

    /**
     * Check if a specific step is completed or skipped.
     */
    public function isOnboardingStepDone(string $step): bool
    {
        $data = $this->getOnboardingData();
        $stepData = $data['steps'][$step] ?? ['completed' => false, 'skipped' => false];

        return $stepData['completed'] || $stepData['skipped'];
    }

    /**
     * Mark the entire onboarding as complete.
     */
    public function markOnboardingComplete(): void
    {
        $this->setOnboardingData([
            'completed' => true,
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Initialize onboarding for a new tenant.
     */
    public function initializeOnboarding(): void
    {
        $this->setOnboardingData([
            'completed' => false,
            'completed_at' => null,
            'current_step' => 1,
            'steps' => [
                'organization' => ['completed' => false, 'skipped' => false],
                'team' => ['completed' => false, 'skipped' => false],
                'integrations' => ['completed' => false, 'skipped' => false],
                'services' => ['completed' => false, 'skipped' => false],
            ],
            'branch_id' => null,
        ]);
    }

    /**
     * Set the main branch ID created during onboarding.
     */
    public function setOnboardingBranchId(string $branchId): void
    {
        $this->setOnboardingData(['branch_id' => $branchId]);
    }

    /**
     * Get the main branch ID created during onboarding.
     */
    public function getOnboardingBranchId(): ?string
    {
        return $this->getOnboardingData()['branch_id'];
    }
}
