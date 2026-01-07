<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PlatformPaymentMethod;
use App\Enums\PlatformPaymentStatus;
use App\Enums\SuperAdminRole;
use App\Enums\SupportLevel;
use App\Enums\TenantStatus;
use App\Livewire\SuperAdmin\Billing\BillingDashboard;
use App\Livewire\SuperAdmin\Billing\InvoiceCreate;
use App\Livewire\SuperAdmin\Billing\InvoiceIndex;
use App\Livewire\SuperAdmin\Billing\InvoiceShow;
use App\Livewire\SuperAdmin\Billing\OverdueInvoices;
use App\Livewire\SuperAdmin\Billing\PaymentIndex;
use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformPayment;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use App\Services\PlatformBillingService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createTestPlan(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => 'Test Plan '.uniqid(),
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'max_members' => 500,
        'max_branches' => 5,
        'storage_quota_gb' => 10,
        'sms_credits_monthly' => 100,
        'support_level' => SupportLevel::Community,
    ], $attributes));
}

beforeEach(function () {
    // Clean up test data
    PlatformInvoice::query()->delete();
    PlatformPayment::query()->delete();
});

describe('page access', function () {
    it('allows owner to view billing dashboard', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        $this->actingAs($owner, 'superadmin')
            ->get(route('superadmin.billing.dashboard'))
            ->assertOk()
            ->assertSee('Billing Dashboard');
    });

    it('allows admin to view billing dashboard', function () {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.billing.dashboard'))
            ->assertOk()
            ->assertSee('Billing Dashboard');
    });

    it('allows support to view billing dashboard', function () {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        $this->actingAs($support, 'superadmin')
            ->get(route('superadmin.billing.dashboard'))
            ->assertOk()
            ->assertSee('Billing Dashboard');
    });

    it('denies guest access to billing dashboard', function () {
        $this->get(route('superadmin.billing.dashboard'))
            ->assertRedirect(route('superadmin.login'));
    });

    it('allows authenticated admin to view invoice list', function () {
        $admin = SuperAdmin::factory()->create();

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.billing.invoices'))
            ->assertOk()
            ->assertSee('Invoices');
    });

    it('allows authenticated admin to view payment list', function () {
        $admin = SuperAdmin::factory()->create();

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.billing.payments'))
            ->assertOk()
            ->assertSee('Payments');
    });

    it('allows authenticated admin to view overdue invoices', function () {
        $admin = SuperAdmin::factory()->create();

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.billing.overdue'))
            ->assertOk()
            ->assertSee('Overdue Invoices');
    });
});

describe('billing dashboard', function () {
    it('displays billing metrics', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(BillingDashboard::class)
            ->assertSee('Revenue YTD')
            ->assertSee('Outstanding')
            ->assertSee('Overdue');
    });

    it('shows overdue invoices section', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(BillingDashboard::class)
            ->assertSee('Overdue Invoices');
    });
});

describe('invoice index', function () {
    it('displays list of invoices', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'invoice-test-tenant',
            'name' => 'Invoice Test Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('invoice-test-tenant')
            ->create([
                'subscription_plan_id' => $plan->id,
            ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceIndex::class)
            ->assertSee($invoice->invoice_number)
            ->assertSee('Invoice Test Church');
    });

    it('can filter invoices by status', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'filter-test-tenant',
            'name' => 'Filter Test Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $draftInvoice = PlatformInvoice::factory()
            ->forTenant('filter-test-tenant')
            ->draft()
            ->create(['subscription_plan_id' => $plan->id]);

        $paidInvoice = PlatformInvoice::factory()
            ->forTenant('filter-test-tenant')
            ->paid()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceIndex::class)
            ->set('status', InvoiceStatus::Draft->value)
            ->assertSee($draftInvoice->invoice_number)
            ->assertDontSee($paidInvoice->invoice_number);
    });

    it('can search invoices', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'search-test-tenant',
            'name' => 'Searchable Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('search-test-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceIndex::class)
            ->set('search', 'Searchable')
            ->assertSee($invoice->invoice_number);
    });

    it('can export invoices to CSV', function () {
        $admin = SuperAdmin::factory()->create();

        $component = Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceIndex::class)
            ->call('exportCsv');

        expect($component->effects['download'])->toBeArray();
        expect($component->effects['download']['name'])->toContain('invoices-');
        expect($component->effects['download']['name'])->toContain('.csv');
    });
});

describe('invoice show', function () {
    it('displays invoice details', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'show-test-tenant',
            'name' => 'Show Test Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('show-test-tenant')
            ->sent()
            ->create(['subscription_plan_id' => $plan->id]);

        PlatformInvoiceItem::create([
            'platform_invoice_id' => $invoice->id,
            'description' => 'Monthly Subscription',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->assertSee($invoice->invoice_number)
            ->assertSee('Show Test Church')
            ->assertSee('Monthly Subscription');
    });

    it('shows payment history for invoice', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'payment-history-tenant',
            'name' => 'Payment History Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('payment-history-tenant')
            ->partial()
            ->create(['subscription_plan_id' => $plan->id]);

        $payment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->successful()
            ->create([
                'amount' => 50.00,
            ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->assertSee($payment->payment_reference);
    });
});

describe('invoice create', function () {
    it('can create a new invoice', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan(['price_monthly' => 100.00]);

        DB::table('tenants')->insert([
            'id' => 'create-invoice-tenant',
            'name' => 'Create Invoice Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceCreate::class)
            ->set('tenantId', 'create-invoice-tenant')
            ->set('billingCycle', 'monthly')
            ->set('dueDate', now()->addDays(14)->format('Y-m-d'))
            ->call('createInvoice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('platform_invoices', [
            'tenant_id' => 'create-invoice-tenant',
            'subscription_plan_id' => $plan->id,
        ]);
    });

    it('validates required fields', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceCreate::class)
            ->set('dueDate', '')
            ->call('createInvoice')
            ->assertHasErrors(['tenantId', 'dueDate']);
    });
});

describe('payment index', function () {
    it('displays list of payments', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'payment-list-tenant',
            'name' => 'Payment List Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('payment-list-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        $payment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->successful()
            ->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(PaymentIndex::class)
            ->assertSee($payment->payment_reference);
    });

    it('can filter payments by status', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'payment-filter-tenant',
            'name' => 'Payment Filter Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('payment-filter-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        $successfulPayment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->successful()
            ->create();

        $pendingPayment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->pending()
            ->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(PaymentIndex::class)
            ->set('status', PlatformPaymentStatus::Successful->value)
            ->assertSee($successfulPayment->payment_reference)
            ->assertDontSee($pendingPayment->payment_reference);
    });

    it('can filter payments by method', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'payment-method-tenant',
            'name' => 'Payment Method Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('payment-method-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        $bankPayment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->viaBankTransfer()
            ->successful()
            ->create();

        $cashPayment = PlatformPayment::factory()
            ->forInvoice($invoice)
            ->viaCash()
            ->successful()
            ->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(PaymentIndex::class)
            ->set('method', PlatformPaymentMethod::BankTransfer->value)
            ->assertSee($bankPayment->payment_reference)
            ->assertDontSee($cashPayment->payment_reference);
    });

    it('can export payments to CSV', function () {
        $admin = SuperAdmin::factory()->create();

        $component = Livewire::actingAs($admin, 'superadmin')
            ->test(PaymentIndex::class)
            ->call('exportCsv');

        expect($component->effects['download'])->toBeArray();
        expect($component->effects['download']['name'])->toContain('payments-');
        expect($component->effects['download']['name'])->toContain('.csv');
    });
});

describe('overdue invoices', function () {
    it('displays overdue invoices', function () {
        $admin = SuperAdmin::factory()->create();
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'overdue-test-tenant',
            'name' => 'Overdue Test Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'contact_email' => 'test@example.com',
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $overdueInvoice = PlatformInvoice::factory()
            ->forTenant('overdue-test-tenant')
            ->overdue()
            ->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(OverdueInvoices::class)
            ->assertSee($overdueInvoice->invoice_number)
            ->assertSee('Overdue Test Church');
    });

    it('shows summary statistics', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(OverdueInvoices::class)
            ->assertSee('Total Overdue')
            ->assertSee('Total Amount')
            ->assertSee('Over 30 Days');
    });

    it('shows all clear message when no overdue invoices', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(OverdueInvoices::class)
            ->assertSee('No overdue invoices');
    });
});

describe('PlatformInvoice model', function () {
    it('generates invoice number on creation', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'invoice-number-tenant',
            'name' => 'Invoice Number Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('invoice-number-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        expect($invoice->invoice_number)->toStartWith('INV-');
    });

    it('calculates days overdue correctly', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'days-overdue-tenant',
            'name' => 'Days Overdue Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('days-overdue-tenant')
            ->create([
                'subscription_plan_id' => $plan->id,
                'due_date' => now()->subDays(10),
                'status' => InvoiceStatus::Overdue,
            ]);

        expect($invoice->daysOverdue())->toBe(10);
    });

    it('detects overdue status correctly', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'overdue-status-tenant',
            'name' => 'Overdue Status Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $overdueInvoice = PlatformInvoice::factory()
            ->forTenant('overdue-status-tenant')
            ->create([
                'subscription_plan_id' => $plan->id,
                'due_date' => now()->subDays(5),
                'status' => InvoiceStatus::Sent,
            ]);

        expect($overdueInvoice->isOverdue())->toBeTrue();
    });

    it('has correct relationships', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'relationships-tenant',
            'name' => 'Relationships Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('relationships-tenant')
            ->create(['subscription_plan_id' => $plan->id]);

        expect($invoice->tenant)->not->toBeNull();
        expect($invoice->subscriptionPlan)->not->toBeNull();
        expect($invoice->items)->toBeEmpty();
        expect($invoice->payments)->toBeEmpty();
    });
});

describe('PlatformBillingService', function () {
    it('can record a full payment', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'manual-payment-tenant',
            'name' => 'Manual Payment Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('manual-payment-tenant')
            ->sent()
            ->create([
                'subscription_plan_id' => $plan->id,
                'total_amount' => 100.00,
                'balance_due' => 100.00,
            ]);

        $service = app(PlatformBillingService::class);
        $payment = $service->recordPayment($invoice, [
            'amount' => 100.00,
            'payment_method' => PlatformPaymentMethod::BankTransfer,
            'notes' => 'Test payment',
        ]);

        expect($payment)->toBeInstanceOf(PlatformPayment::class);
        expect($payment->amount)->toBe('100.00');
        expect($payment->status)->toBe(PlatformPaymentStatus::Successful);

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Paid);
        expect((float) $invoice->balance_due)->toBe(0.0);
    });

    it('can record a partial payment', function () {
        $plan = createTestPlan();

        DB::table('tenants')->insert([
            'id' => 'partial-payment-tenant',
            'name' => 'Partial Payment Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = PlatformInvoice::factory()
            ->forTenant('partial-payment-tenant')
            ->sent()
            ->create([
                'subscription_plan_id' => $plan->id,
                'total_amount' => 100.00,
                'balance_due' => 100.00,
            ]);

        $service = app(PlatformBillingService::class);
        $payment = $service->recordPayment($invoice, [
            'amount' => 50.00,
            'payment_method' => PlatformPaymentMethod::Cash,
        ]);

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Partial);
        expect((float) $invoice->balance_due)->toBe(50.0);
    });
});

describe('activity logging', function () {
    it('logs export activity for invoices', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(InvoiceIndex::class)
            ->call('exportCsv');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $admin->id,
            'action' => 'export_invoices',
        ]);
    });

    it('logs export activity for payments', function () {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(PaymentIndex::class)
            ->call('exportCsv');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $admin->id,
            'action' => 'export_payments',
        ]);
    });
});
