<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformInvoiceItem>
 */
class PlatformInvoiceItemFactory extends Factory
{
    protected $model = PlatformInvoiceItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 200);

        return [
            'platform_invoice_id' => PlatformInvoice::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ];
    }

    public function forInvoice(PlatformInvoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'platform_invoice_id' => $invoice->id,
        ]);
    }

    public function subscriptionFee(float $amount = 100.00): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Monthly Subscription Fee',
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
        ]);
    }

    public function smsCredits(int $quantity = 100, float $unitPrice = 0.05): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'SMS Credits',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ]);
    }

    public function additionalStorage(int $gb = 5, float $pricePerGb = 2.00): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Additional Storage ('.$gb.' GB)',
            'quantity' => $gb,
            'unit_price' => $pricePerGb,
            'total' => $gb * $pricePerGb,
        ]);
    }
}
