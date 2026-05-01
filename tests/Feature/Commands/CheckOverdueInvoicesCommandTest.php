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
        'id' => 'overdue-cmd-tenant',
        'name' => 'Overdue Command Church',
        'status' => TenantStatus::Active,
    ]));
});

it('marks past-due Draft invoices as Overdue', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->draft()
        ->create(['due_date' => now()->subDays(2)]);

    $this->artisan('billing:check-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

it('marks past-due Sent invoices as Overdue', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->subDays(2)]);

    $this->artisan('billing:check-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

it('leaves Paid invoices alone', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->paid()
        ->create(['due_date' => now()->subDays(2)]);

    $this->artisan('billing:check-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('leaves invoices whose due_date is in the future alone', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->addDays(3)]);

    $this->artisan('billing:check-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});
