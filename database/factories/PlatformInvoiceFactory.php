<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformInvoice>
 */
class PlatformInvoiceFactory extends Factory
{
    protected $model = PlatformInvoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $taxAmount = 0;
        $discountAmount = 0;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        $issueDate = now();
        $dueDate = now()->addDays(14);

        return [
            'tenant_id' => fn () => Tenant::factory()->create()->id,
            'subscription_plan_id' => null,
            'billing_period' => now()->format('F Y'),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'balance_due' => $totalAmount,
            'status' => InvoiceStatus::Draft,
            'currency' => 'GHS',
            'notes' => null,
            'metadata' => null,
            'sent_at' => null,
            'paid_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'sent_at' => now()->subDays(7),
            'paid_at' => now(),
            'amount_paid' => $attributes['total_amount'],
            'balance_due' => 0,
        ]);
    }

    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $partialPayment = $attributes['total_amount'] / 2;

            return [
                'status' => InvoiceStatus::Partial,
                'sent_at' => now()->subDays(7),
                'amount_paid' => $partialPayment,
                'balance_due' => $attributes['total_amount'] - $partialPayment,
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Overdue,
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(16),
            'sent_at' => now()->subDays(28),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Cancelled,
            'notes' => 'Invoice cancelled: Customer request',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Refunded,
            'paid_at' => now()->subDays(5),
            'amount_paid' => $attributes['total_amount'],
            'balance_due' => 0,
        ]);
    }

    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    public function withDiscount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $newTotal = $attributes['subtotal'] - $amount + $attributes['tax_amount'];

            return [
                'discount_amount' => $amount,
                'total_amount' => $newTotal,
                'balance_due' => $newTotal - $attributes['amount_paid'],
            ];
        });
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => now()->format('F Y'),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => now()->format('Y').' Annual',
            'period_start' => now()->startOfYear(),
            'period_end' => now()->endOfYear(),
        ]);
    }
}
