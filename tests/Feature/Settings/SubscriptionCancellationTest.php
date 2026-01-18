<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\TenantStatus;
use App\Livewire\Settings\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create([
        'name' => 'Test Church',
        'contact_email' => 'church@test.com',
        'status' => TenantStatus::Active,
    ]);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    Route::middleware(['web'])->group(base_path('routes/tenant.php'));
    Route::middleware(['web'])->group(base_path('routes/web.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 100,
        'price_annual' => 1000,
        'is_active' => true,
    ]);

    $this->tenant->update(['subscription_id' => $this->plan->id]);

    $this->admin = User::factory()->create(['email' => 'admin@test.com']);
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Cache::flush();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// CANCELLATION FLOW TESTS
// ============================================

it('shows cancel button when subscription is active', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->assertSee('Danger Zone')
        ->assertSee('Cancel Subscription');
});

it('does not show cancel button when subscription is already cancelled', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->assertDontSee('Danger Zone')
        ->assertSee('Subscription Cancelled');
});

it('opens confirmation modal when clicking cancel', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->call('confirmCancellation')
        ->assertSet('showCancelModal', true);
});

it('requires cancellation reason', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->call('confirmCancellation')
        ->set('cancellationReason', '')
        ->call('cancelSubscription')
        ->assertHasErrors(['cancellationReason' => 'required']);
});

it('requires cancellation reason to be at least 10 characters', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->call('confirmCancellation')
        ->set('cancellationReason', 'short')
        ->call('cancelSubscription')
        ->assertHasErrors(['cancellationReason' => 'min']);
});

it('cancels subscription with valid reason', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->call('confirmCancellation')
        ->set('cancellationReason', 'I am switching to a different service provider')
        ->call('cancelSubscription')
        ->assertSet('showCancelModal', false)
        ->assertSet('cancellationReason', '');

    $this->tenant->refresh();

    expect($this->tenant->isCancelled())->toBeTrue();
    expect($this->tenant->cancellation_reason)->toBe('I am switching to a different service provider');
    expect($this->tenant->subscription_ends_at)->not->toBeNull();
});

// ============================================
// CANCELLATION STATUS DISPLAY TESTS
// ============================================

it('shows cancellation banner with end date', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->assertSee('Subscription Cancelled')
        ->assertSee('Reactivate Subscription');
});

it('shows days remaining when cancelled', function (): void {
    $this->tenant->update([
        'cancelled_at' => now(),
        'cancellation_reason' => 'Test',
        'subscription_ends_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->assertSee('days remaining');
});

// ============================================
// REACTIVATION TESTS
// ============================================

it('reactivates cancelled subscription', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->call('reactivateSubscription');

    $this->tenant->refresh();

    expect($this->tenant->isCancelled())->toBeFalse();
    expect($this->tenant->cancelled_at)->toBeNull();
    expect($this->tenant->cancellation_reason)->toBeNull();
    expect($this->tenant->subscription_ends_at)->toBeNull();
});

// ============================================
// TENANT MODEL CANCELLATION TESTS
// ============================================

it('sets cancelled_at when cancelling', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    expect($this->tenant->cancelled_at)->not->toBeNull();
});

it('sets subscription_ends_at to end of month', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    expect($this->tenant->subscription_ends_at->format('Y-m-d'))
        ->toBe(now()->endOfMonth()->format('Y-m-d'));
});

it('isCancelled returns true when cancelled', function (): void {
    expect($this->tenant->isCancelled())->toBeFalse();

    $this->tenant->cancelSubscription('Test reason');

    expect($this->tenant->isCancelled())->toBeTrue();
});

it('isInCancellationGracePeriod returns true when cancelled but not expired', function (): void {
    $this->tenant->cancelSubscription('Test reason');

    expect($this->tenant->isInCancellationGracePeriod())->toBeTrue();
});

it('hasCancellationExpired returns true when past end date', function (): void {
    $this->tenant->update([
        'cancelled_at' => now()->subDays(10),
        'cancellation_reason' => 'Test',
        'subscription_ends_at' => now()->subDay(),
    ]);

    expect($this->tenant->hasCancellationExpired())->toBeTrue();
});

it('subscriptionDaysRemaining returns correct count', function (): void {
    $this->tenant->update([
        'cancelled_at' => now(),
        'cancellation_reason' => 'Test',
        'subscription_ends_at' => now()->addDays(10)->startOfDay(),
    ]);

    // diffInDays counts full days between dates
    expect($this->tenant->subscriptionDaysRemaining())->toBeGreaterThanOrEqual(9);
    expect($this->tenant->subscriptionDaysRemaining())->toBeLessThanOrEqual(10);
});

// ============================================
// NO PLAN TESTS
// ============================================

it('does not show cancel button when no plan', function (): void {
    $this->tenant->update(['subscription_id' => null]);
    Cache::forget("tenant:{$this->tenant->id}:subscription_plan");

    Livewire::actingAs($this->admin)
        ->test(Subscription::class)
        ->assertDontSee('Danger Zone')
        ->assertDontSee('Cancel Subscription');
});
