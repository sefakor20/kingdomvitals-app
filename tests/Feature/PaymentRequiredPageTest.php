<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Models\PlatformInvoice;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    Route::middleware(['web'])->group(base_path('routes/tenant.php'));
    Route::middleware(['web'])->group(base_path('routes/web.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create(['email' => 'admin-pr@test.com']);
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Cache::flush();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('renders the payment-required page with past-due invoice details', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create([
            'invoice_number' => 'INV-202604-0001',
            'billing_period' => 'April 2026',
            'due_date' => now()->subDays(10),
            'balance_due' => 100.00,
            'currency' => 'GHS',
        ]);

    $this->actingAs($this->admin)
        ->get(route('payment.required'))
        ->assertOk()
        ->assertSee('Payment Required')
        ->assertSee($invoice->invoice_number)
        ->assertSee('April 2026')
        ->assertSee(route('payments.history'))
        ->assertSee(route('subscription.show'))
        ->assertSee(route('logout'));
});

it('does not list paid invoices on the payment-required page', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->paid()
        ->create(['invoice_number' => 'INV-PAID-0001']);

    $this->actingAs($this->admin)
        ->get(route('payment.required'))
        ->assertOk()
        ->assertDontSee('INV-PAID-0001');
});
