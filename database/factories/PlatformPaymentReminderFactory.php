<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformInvoice;
use App\Models\PlatformPaymentReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformPaymentReminder>
 */
class PlatformPaymentReminderFactory extends Factory
{
    protected $model = PlatformPaymentReminder::class;

    public function definition(): array
    {
        return [
            'platform_invoice_id' => PlatformInvoice::factory(),
            'type' => PlatformPaymentReminder::TYPE_UPCOMING,
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'sent_at' => now(),
            'recipient_email' => fake()->email(),
            'recipient_phone' => null,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlatformPaymentReminder::TYPE_UPCOMING,
        ]);
    }

    public function overdue7(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlatformPaymentReminder::TYPE_OVERDUE_7,
        ]);
    }

    public function overdue14(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlatformPaymentReminder::TYPE_OVERDUE_14,
        ]);
    }

    public function overdue30(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlatformPaymentReminder::TYPE_OVERDUE_30,
        ]);
    }

    public function finalNotice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlatformPaymentReminder::TYPE_FINAL_NOTICE,
        ]);
    }

    public function viaEmail(?string $email = null): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => PlatformPaymentReminder::CHANNEL_EMAIL,
            'recipient_email' => $email ?? fake()->email(),
            'recipient_phone' => null,
        ]);
    }

    public function viaSms(?string $phone = null): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => PlatformPaymentReminder::CHANNEL_SMS,
            'recipient_email' => null,
            'recipient_phone' => $phone ?? fake()->phoneNumber(),
        ]);
    }

    public function forInvoice(PlatformInvoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'platform_invoice_id' => $invoice->id,
        ]);
    }
}
