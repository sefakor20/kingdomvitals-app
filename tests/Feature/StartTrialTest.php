<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Livewire\Landing\StartTrial;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\TrialSignup;
use App\Models\User;
use App\Notifications\TenantAdminInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    // Route is scoped to central domains; simulate being on kingdomvitals-app.test.
    $this->serverVariables = ['HTTP_HOST' => 'kingdomvitals-app.test'];
});

it('renders the start-trial page', function (): void {
    $this->get('http://kingdomvitals-app.test/start-trial')
        ->assertOk()
        ->assertSeeLivewire(StartTrial::class);
});

it('rejects invalid subdomains', function (): void {
    Livewire::test(StartTrial::class)
        ->set('churchName', 'Test Church')
        ->set('subdomain', 'BAD!')
        ->set('adminName', 'Pastor Joe')
        ->set('adminEmail', 'joe@example.com')
        ->call('submit')
        ->assertHasErrors(['subdomain']);
});

it('rejects reserved subdomains', function (): void {
    Livewire::test(StartTrial::class)
        ->set('churchName', 'Test Church')
        ->set('subdomain', 'www')
        ->set('adminName', 'Pastor Joe')
        ->set('adminEmail', 'joe@example.com')
        ->call('submit')
        ->assertHasErrors(['subdomain']);
});

it('rejects subdomains already taken', function (): void {
    $existing = Tenant::create(['id' => 'existing', 'name' => 'Existing']);
    $existing->domains()->create(['domain' => 'stlukes.kingdomvitals-app.test']);

    Livewire::test(StartTrial::class)
        ->set('churchName', 'Test Church')
        ->set('subdomain', 'stlukes')
        ->set('adminName', 'Pastor Joe')
        ->set('adminEmail', 'joe@example.com')
        ->call('submit')
        ->assertHasErrors(['subdomain']);
});

it('rejects emails that have already started a trial', function (): void {
    TrialSignup::create(['email' => 'taken@example.com']);

    Livewire::test(StartTrial::class)
        ->set('churchName', 'Test Church')
        ->set('subdomain', 'freshname')
        ->set('adminName', 'Pastor Joe')
        ->set('adminEmail', 'taken@example.com')
        ->call('submit')
        ->assertHasErrors(['adminEmail']);
});

it('creates tenant, domain, admin user and sends notification on valid submit', function (): void {
    Notification::fake();

    Livewire::test(StartTrial::class)
        ->set('churchName', 'St Lukes Anglican')
        ->set('subdomain', 'stlukes')
        ->set('adminName', 'Pastor Jane')
        ->set('adminEmail', 'jane@stlukes.org')
        ->set('contactEmail', 'hello@stlukes.org')
        ->set('contactPhone', '+233501234567')
        ->set('address', '12 Church Lane, Accra')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('createdDomain', 'stlukes.kingdomvitals-app.test');

    $tenant = Tenant::where('name', 'St Lukes Anglican')->first();
    expect($tenant)->not->toBeNull()
        ->and($tenant->status)->toBe(TenantStatus::Trial)
        ->and($tenant->trial_ends_at->diffInHours(now()->addDays(14)))->toBeLessThan(1)
        ->and($tenant->contact_email)->toBe('hello@stlukes.org')
        ->and($tenant->contact_phone)->toBe('+233501234567')
        ->and($tenant->address)->toBe('12 Church Lane, Accra');

    expect(Domain::where('domain', 'stlukes.kingdomvitals-app.test')->exists())->toBeTrue();

    $signup = TrialSignup::where('email', 'jane@stlukes.org')->first();
    expect($signup->tenant_id)->toBe($tenant->id);

    $tenant->run(function (): void {
        $user = User::where('email', 'jane@stlukes.org')->first();
        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Pastor Jane')
            ->and($user->hasVerifiedEmail())->toBeTrue();
    });

    Notification::assertSentTo(
        [$tenant->run(fn () => User::where('email', 'jane@stlukes.org')->first())],
        TenantAdminInvitationNotification::class
    );
});

it('throttles repeated submissions from the same ip', function (): void {
    // 5 allowed hits, the 6th should throttle. Use different (valid but non-matching) emails so
    // email-uniqueness doesn't short-circuit before the throttle kicks in.
    for ($i = 1; $i <= 5; $i++) {
        Livewire::test(StartTrial::class)
            ->set('churchName', 'Church '.$i)
            ->set('subdomain', 'church'.$i)
            ->set('adminName', 'Pastor '.$i)
            ->set('adminEmail', "pastor{$i}@example.com")
            ->call('submit');
    }

    Livewire::test(StartTrial::class)
        ->set('churchName', 'Church Six')
        ->set('subdomain', 'church6')
        ->set('adminName', 'Pastor Six')
        ->set('adminEmail', 'pastor6@example.com')
        ->call('submit')
        ->assertHasErrors(['churchName']);
});
