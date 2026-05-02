<?php

declare(strict_types=1);

use App\Mail\PlatformInvoiceMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Mail\SubscriptionCancelledMail;
use App\Mail\SubscriptionExpiringMail;
use App\Mail\SubscriptionReactivatedMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Tenant;

beforeEach(function (): void {
    config([
        'mail.billing.from.address' => 'billing@kingdomvitals.app',
        'mail.billing.from.name' => 'Kingdom Vitals Billing',
        'mail.billing.reply_to.address' => 'support@kingdomvitals.app',
        'mail.billing.reply_to.name' => 'Kingdom Vitals Support',
    ]);
});

dataset('billing_mailables_with_tenant', [
    'subscription expiring' => [SubscriptionExpiringMail::class],
    'subscription cancelled' => [SubscriptionCancelledMail::class],
    'subscription reactivated' => [SubscriptionReactivatedMail::class],
]);

it('sends tenant subscription mailables from the billing address with support reply-to', function (string $mailable): void {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'billing-mail-tenant-'.str(class_basename($mailable))->snake(),
        'name' => 'Billing Mail Test Church',
    ]));

    $envelope = (new $mailable($tenant))->envelope();

    expect($envelope->from->address)->toBe('billing@kingdomvitals.app')
        ->and($envelope->from->name)->toBe('Kingdom Vitals Billing')
        ->and($envelope->replyTo)->toHaveCount(1)
        ->and($envelope->replyTo[0]->address)->toBe('support@kingdomvitals.app')
        ->and($envelope->replyTo[0]->name)->toBe('Kingdom Vitals Support');
})->with('billing_mailables_with_tenant');

it('sends PlatformInvoiceMail from the billing address with support reply-to', function (): void {
    $invoice = new PlatformInvoice(['invoice_number' => 'INV-TEST-0001']);

    $envelope = (new PlatformInvoiceMail($invoice))->envelope();

    expect($envelope->from->address)->toBe('billing@kingdomvitals.app')
        ->and($envelope->replyTo[0]->address)->toBe('support@kingdomvitals.app');
});

it('sends PlatformPaymentReceivedMail from the billing address with support reply-to', function (): void {
    $invoice = new PlatformInvoice(['invoice_number' => 'INV-TEST-0002']);
    $payment = new PlatformPayment;
    $payment->setRelation('invoice', $invoice);

    $envelope = (new PlatformPaymentReceivedMail($payment, attachInvoice: false))->envelope();

    expect($envelope->from->address)->toBe('billing@kingdomvitals.app')
        ->and($envelope->replyTo[0]->address)->toBe('support@kingdomvitals.app');
});
