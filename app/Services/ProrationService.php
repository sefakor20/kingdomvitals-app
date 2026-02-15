<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;

class ProrationService
{
    /**
     * Calculate proration for a plan change.
     *
     * @return array{
     *     days_remaining: int,
     *     days_used: int,
     *     days_in_period: int,
     *     old_plan_credit: float,
     *     new_plan_cost: float,
     *     amount_due: float,
     *     credit_generated: float,
     *     change_type: string,
     *     old_daily_rate: float,
     *     new_daily_rate: float
     * }
     */
    public function calculatePlanChange(
        Tenant $tenant,
        SubscriptionPlan $newPlan,
        BillingCycle $newCycle
    ): array {
        $currentPlan = $tenant->subscriptionPlan;
        $currentCycle = $tenant->getBillingCycle();

        // If no current period, no proration needed
        if (! $tenant->current_period_end || ! $currentPlan) {
            return $this->noProrationResult($newPlan, $newCycle);
        }

        $periodEnd = Carbon::parse($tenant->current_period_end);
        $periodStart = Carbon::parse($tenant->current_period_start);

        // If period has already ended, no proration
        if ($periodEnd->isPast()) {
            return $this->noProrationResult($newPlan, $newCycle);
        }

        $today = now()->startOfDay();
        $daysInPeriod = (int) $periodStart->diffInDays($periodEnd);
        $daysUsed = (int) $periodStart->diffInDays($today);
        $daysRemaining = (int) max(0, $today->diffInDays($periodEnd));

        // Handle edge case: if on last day of period, skip proration
        if ($daysRemaining <= 0) {
            return $this->noProrationResult($newPlan, $newCycle);
        }

        // Calculate daily rates
        $oldDailyRate = $this->getDailyRate($currentPlan, $currentCycle ?? BillingCycle::Monthly, $daysInPeriod);
        $newDailyRate = $this->getDailyRate($newPlan, $newCycle, $daysInPeriod);

        // Calculate proration amounts
        $oldPlanCredit = round($oldDailyRate * $daysRemaining, 2);
        $newPlanCost = round($newDailyRate * $daysRemaining, 2);
        $amountDue = round($newPlanCost - $oldPlanCredit, 2);

        // Determine if credit is generated (for downgrades)
        $creditGenerated = $amountDue < 0 ? abs($amountDue) : 0;
        if ($creditGenerated > 0) {
            $amountDue = 0;
        }

        $changeType = $this->determineChangeType(
            $currentPlan,
            $newPlan,
            $currentCycle ?? BillingCycle::Monthly,
            $newCycle
        );

        return [
            'days_remaining' => $daysRemaining,
            'days_used' => $daysUsed,
            'days_in_period' => $daysInPeriod,
            'old_plan_credit' => $oldPlanCredit,
            'new_plan_cost' => $newPlanCost,
            'amount_due' => $amountDue,
            'credit_generated' => $creditGenerated,
            'change_type' => $changeType,
            'old_daily_rate' => round($oldDailyRate, 4),
            'new_daily_rate' => round($newDailyRate, 4),
        ];
    }

    /**
     * Get the daily rate for a plan based on billing cycle.
     */
    public function getDailyRate(SubscriptionPlan $plan, BillingCycle $cycle, int $daysInPeriod): float
    {
        if ($daysInPeriod <= 0) {
            return 0;
        }

        $price = $cycle === BillingCycle::Annual
            ? (float) $plan->price_annual
            : (float) $plan->price_monthly;

        // For annual plans, we need to calculate the daily rate over the year
        // but prorate based on the current period length
        if ($cycle === BillingCycle::Annual) {
            // Annual price divided by 365 days
            return $price / 365;
        }

        // For monthly, divide by days in the period
        return $price / $daysInPeriod;
    }

    /**
     * Calculate days remaining in the current billing period.
     */
    public function getDaysRemaining(Tenant $tenant): int
    {
        if (! $tenant->current_period_end) {
            return 0;
        }

        $periodEnd = Carbon::parse($tenant->current_period_end);

        if ($periodEnd->isPast()) {
            return 0;
        }

        return (int) max(0, now()->startOfDay()->diffInDays($periodEnd));
    }

    /**
     * Determine the type of plan change.
     */
    public function determineChangeType(
        SubscriptionPlan $currentPlan,
        SubscriptionPlan $newPlan,
        BillingCycle $currentCycle,
        BillingCycle $newCycle
    ): string {
        // Check for billing cycle change
        if ($currentCycle !== $newCycle) {
            return 'cycle_change';
        }

        // Compare plan prices to determine upgrade vs downgrade
        $currentPrice = $currentCycle === BillingCycle::Annual
            ? (float) $currentPlan->price_annual
            : (float) $currentPlan->price_monthly;

        $newPrice = $newCycle === BillingCycle::Annual
            ? (float) $newPlan->price_annual
            : (float) $newPlan->price_monthly;

        if ($newPrice > $currentPrice) {
            return 'upgrade';
        }

        if ($newPrice < $currentPrice) {
            return 'downgrade';
        }

        return 'lateral';
    }

    /**
     * Check if proration should be applied for this change.
     */
    public function shouldApplyProration(Tenant $tenant): bool
    {
        // No proration if no active subscription
        if (! $tenant->subscription_id) {
            return false;
        }

        // No proration if no billing period set
        if (! $tenant->current_period_end || ! $tenant->current_period_start) {
            return false;
        }

        // No proration if period has ended
        $periodEnd = Carbon::parse($tenant->current_period_end);
        if ($periodEnd->isPast()) {
            return false;
        }

        // No proration if on last day of period
        $daysRemaining = $this->getDaysRemaining($tenant);
        return $daysRemaining > 0;
    }

    /**
     * Generate a result for when no proration is needed.
     *
     * @return array{
     *     days_remaining: int,
     *     days_used: int,
     *     days_in_period: int,
     *     old_plan_credit: float,
     *     new_plan_cost: float,
     *     amount_due: float,
     *     credit_generated: float,
     *     change_type: string,
     *     old_daily_rate: float,
     *     new_daily_rate: float
     * }
     */
    private function noProrationResult(SubscriptionPlan $newPlan, BillingCycle $newCycle): array
    {
        $fullPrice = $newCycle === BillingCycle::Annual
            ? (float) $newPlan->price_annual
            : (float) $newPlan->price_monthly;

        return [
            'days_remaining' => 0,
            'days_used' => 0,
            'days_in_period' => 0,
            'old_plan_credit' => 0,
            'new_plan_cost' => $fullPrice,
            'amount_due' => $fullPrice,
            'credit_generated' => 0,
            'change_type' => 'new_subscription',
            'old_daily_rate' => 0,
            'new_daily_rate' => 0,
        ];
    }
}
