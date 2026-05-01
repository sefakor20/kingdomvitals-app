<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Mail\PlatformInvoiceMail;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();

    $this->plan = SubscriptionPlan::create([
        'name' => 'Generation Test Plan',
        'slug' => 'generation-test-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'is_active' => true,
    ]);
});

it('marks generated invoices as Sent even when tenant has no contact email', function (): void {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'no-email-tenant',
        'name' => 'No Email Church',
        'status' => TenantStatus::Active,
        'subscription_id' => $this->plan->id,
        'contact_email' => null,
    ]));

    $this->artisan('billing:generate-invoices')->assertSuccessful();

    $invoice = PlatformInvoice::where('tenant_id', $tenant->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Sent);

    Mail::assertNothingOutgoing();
});

it('marks generated invoices as Sent and queues email when tenant has contact email', function (): void {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'with-email-tenant',
        'name' => 'With Email Church',
        'status' => TenantStatus::Active,
        'subscription_id' => $this->plan->id,
        'contact_email' => 'billing@withemail.test',
    ]));

    $this->artisan('billing:generate-invoices')->assertSuccessful();

    $invoice = PlatformInvoice::where('tenant_id', $tenant->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Sent);

    Mail::assertQueued(PlatformInvoiceMail::class);
});
