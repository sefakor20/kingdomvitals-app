<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Livewire\Settings\PaymentHistory;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {    // Load routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));
    Route::middleware(['web'])->group(base_path('routes/web.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create(['email' => 'admin@test.com']);
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

// ============================================
// MOUNT / INITIALIZATION TESTS
// ============================================

it('renders payment history page', function (): void {
    Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class)
        ->assertOk();
});

it('shows empty state when no billing history', function (): void {
    Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class)
        ->assertSee('No billing history')
        ->assertSee('Your invoices and payment records will appear here');
});

// ============================================
// INVOICES DISPLAY TESTS
// ============================================

it('displays invoices for current tenant', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create([
            'subscription_plan_id' => $plan->id,
            'total_amount' => 150.00,
        ]);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertSee($invoice->invoice_number)
        ->assertSee('Pro')
        ->assertSee('Sent');
});

it('does not display invoices from other tenants', function (): void {
    $otherTenant = Tenant::create(['name' => 'Other Church']);

    PlatformInvoice::factory()
        ->forTenant($otherTenant)
        ->sent()
        ->create(['invoice_number' => 'INV-OTHER-001']);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertDontSee('INV-OTHER-001');
});

it('orders invoices by issue date descending', function (): void {
    $olderInvoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create(['issue_date' => now()->subDays(10)]);

    $newerInvoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create(['issue_date' => now()]);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $invoices = $component->instance()->invoices;

    expect($invoices->first()->id)->toBe($newerInvoice->id);
    expect($invoices->last()->id)->toBe($olderInvoice->id);
});

it('displays invoice status with correct badge color', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->paid()
        ->create();

    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->overdue()
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertSee('Paid')
        ->assertSee('Overdue');
});

// ============================================
// PAYMENTS DISPLAY TESTS
// ============================================

it('displays payments for current tenant', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    $payment = PlatformPayment::factory()
        ->forInvoice($invoice)
        ->successful()
        ->viaPaystack()
        ->create(['amount' => 100.00]);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertSee($payment->payment_reference)
        ->assertSee('Paystack')
        ->assertSee('Successful');
});

it('does not display payments from other tenants', function (): void {
    $otherTenant = Tenant::create(['name' => 'Other Church']);

    $invoice = PlatformInvoice::factory()
        ->forTenant($otherTenant)
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->create(['payment_reference' => 'PAY-OTHER-001']);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertDontSee('PAY-OTHER-001');
});

it('orders payments by paid_at descending', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    $olderPayment = PlatformPayment::factory()
        ->forInvoice($invoice)
        ->successful()
        ->create(['paid_at' => now()->subDays(10)]);

    $newerPayment = PlatformPayment::factory()
        ->forInvoice($invoice)
        ->successful()
        ->create(['paid_at' => now()]);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $payments = $component->instance()->payments;

    expect($payments->first()->id)->toBe($newerPayment->id);
    expect($payments->last()->id)->toBe($olderPayment->id);
});

it('displays payment method with icon', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->viaBankTransfer()
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->viaCash()
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $component->assertSee('Bank Transfer')
        ->assertSee('Cash');
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

it('invoices computed property returns tenant invoices', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->count(3)
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    expect($component->instance()->invoices)->toHaveCount(3);
});

it('payments computed property returns tenant payments', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->count(2)
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    expect($component->instance()->payments)->toHaveCount(2);
});

it('hasBillingHistory returns true when invoices exist', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    expect($component->instance()->hasBillingHistory)->toBeTrue();
});

it('hasBillingHistory returns true when payments exist', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    expect($component->instance()->hasBillingHistory)->toBeTrue();
});

it('hasBillingHistory returns false when no billing history', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    expect($component->instance()->hasBillingHistory)->toBeFalse();
});

// ============================================
// EAGER LOADING TESTS
// ============================================

it('loads subscription plan with invoices', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Enterprise',
        'slug' => 'enterprise',
        'price_monthly' => 500,
        'price_annual' => 5000,
        'is_active' => true,
    ]);

    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create(['subscription_plan_id' => $plan->id]);

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $invoices = $component->instance()->invoices;

    expect($invoices->first()->relationLoaded('subscriptionPlan'))->toBeTrue();
    expect($invoices->first()->subscriptionPlan->name)->toBe('Enterprise');
});

it('loads invoice with payments', function (): void {
    $invoice = PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->create();

    PlatformPayment::factory()
        ->forInvoice($invoice)
        ->create();

    $component = Livewire::actingAs($this->admin)
        ->test(PaymentHistory::class);

    $payments = $component->instance()->payments;

    expect($payments->first()->relationLoaded('invoice'))->toBeTrue();
});
