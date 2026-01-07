<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformPayment>
 */
class PlatformPaymentFactory extends Factory
{
    protected $model = PlatformPayment::class;

    public function definition(): array
    {
        return [
            'platform_invoice_id' => PlatformInvoice::factory(),
            'tenant_id' => fn () => Tenant::factory()->create()->id,
            'paystack_reference' => null,
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'GHS',
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'status' => PlatformPaymentStatus::Pending,
            'notes' => null,
            'metadata' => null,
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlatformPaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlatformPaymentStatus::Successful,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlatformPaymentStatus::Failed,
            'paid_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlatformPaymentStatus::Refunded,
            'paid_at' => now()->subDays(5),
        ]);
    }

    public function viaPaystack(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PlatformPaymentMethod::Paystack,
            'paystack_reference' => 'PS_'.strtoupper(fake()->unique()->bothify('##??##??##??')),
        ]);
    }

    public function viaBankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PlatformPaymentMethod::BankTransfer,
        ]);
    }

    public function viaCash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PlatformPaymentMethod::Cash,
        ]);
    }

    public function viaCheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PlatformPaymentMethod::Cheque,
        ]);
    }

    public function forInvoice(PlatformInvoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->balance_due,
        ]);
    }

    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
