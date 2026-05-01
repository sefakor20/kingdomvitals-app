<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Http\Middleware\EnsureTenantActive;
use App\Models\PlatformInvoice;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    Route::middleware(['web'])->group(base_path('routes/tenant.php'));
    Route::middleware(['web'])->group(base_path('routes/web.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create(['email' => 'admin-mw@test.com']);
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

function runTenantActiveMiddleware(string $routeName, ?callable $reachedNext = null): Symfony\Component\HttpFoundation\Response
{
    $request = Request::create('/some-path', 'GET');
    $request->setRouteResolver(fn () => (new Illuminate\Routing\Route('GET', '/some-path', []))
        ->name($routeName)
        ->bind($request));

    $middleware = app(EnsureTenantActive::class);

    return $middleware->handle($request, function ($req) use ($reachedNext) {
        if ($reachedNext) {
            $reachedNext($req);
        }

        return new Response('ok', 200);
    });
}

it('passes through when tenant has no past-due invoices', function (): void {
    $this->actingAs($this->admin);

    $response = runTenantActiveMiddleware('dashboard');

    expect($response->getStatusCode())->toBe(200);
});

it('redirects to payment.required when tenant has a past-due Sent invoice', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->subDay()]);

    $this->actingAs($this->admin);

    $response = runTenantActiveMiddleware('dashboard');

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toContain('/payment-required');
});

it('redirects to payment.required when tenant has a past-due Draft invoice', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->draft()
        ->create(['due_date' => now()->subDay()]);

    $this->actingAs($this->admin);

    $response = runTenantActiveMiddleware('dashboard');

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toContain('/payment-required');
});

it('does not redirect when invoice due_date is in the future', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->addDays(3)]);

    $this->actingAs($this->admin);

    $response = runTenantActiveMiddleware('dashboard');

    expect($response->getStatusCode())->toBe(200);
});

it('allows access to whitelisted billing routes even when past-due', function (): void {
    PlatformInvoice::factory()
        ->forTenant($this->tenant)
        ->sent()
        ->create(['due_date' => now()->subDay()]);

    $this->actingAs($this->admin);

    foreach (['payments.history', 'subscription.show', 'plans.index', 'payment.required', 'logout'] as $name) {
        $response = runTenantActiveMiddleware($name);
        expect($response->getStatusCode())->toBe(200);
    }
});
