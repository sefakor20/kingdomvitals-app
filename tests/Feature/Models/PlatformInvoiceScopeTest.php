<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'scope-test-tenant',
        'name' => 'Scope Test Church',
        'status' => TenantStatus::Active,
    ]));
});

it('pastDue includes Draft invoices with past due_date', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->draft()
        ->create(['due_date' => now()->subDay()]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->toContain($invoice->id);
});

it('pastDue includes Sent invoices with past due_date', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->subDay()]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->toContain($invoice->id);
});

it('pastDue includes Partial invoices with past due_date', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->partial()
        ->create(['due_date' => now()->subDay()]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->toContain($invoice->id);
});

it('pastDue includes Overdue invoices', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->overdue()
        ->create();

    expect(PlatformInvoice::pastDue()->pluck('id'))->toContain($invoice->id);
});

it('pastDue excludes Paid invoices', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->paid()
        ->create(['due_date' => now()->subDay()]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->not->toContain($invoice->id);
});

it('pastDue excludes Cancelled invoices', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create([
            'status' => InvoiceStatus::Cancelled,
            'due_date' => now()->subDay(),
        ]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->not->toContain($invoice->id);
});

it('pastDue excludes Refunded invoices', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create([
            'status' => InvoiceStatus::Refunded,
            'due_date' => now()->subDay(),
        ]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->not->toContain($invoice->id);
});

it('pastDue excludes invoices whose due_date is in the future', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->addDays(3)]);

    expect(PlatformInvoice::pastDue()->pluck('id'))->not->toContain($invoice->id);
});
