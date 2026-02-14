<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\Currency;
use App\Livewire\Settings\Organization;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

describe('Organization Currency Settings', function () {
    it('loads default currency on mount', function (): void {
        $component = Livewire::actingAs($this->admin)
            ->test(Organization::class);

        expect($component->instance()->currency)->toBe('GHS');
    });

    it('loads tenant currency on mount', function (): void {
        $this->tenant->update(['currency' => Currency::USD]);

        $component = Livewire::actingAs($this->admin)
            ->test(Organization::class);

        expect($component->instance()->currency)->toBe('USD');
    });

    it('saves currency and dispatches event', function (): void {
        $component = Livewire::actingAs($this->admin)
            ->test(Organization::class)
            ->set('currency', 'USD')
            ->call('saveCurrency')
            ->assertDispatched('currency-saved');

        $this->tenant->refresh();
        expect($this->tenant->getCurrencyCode())->toBe('USD');
    });

    it('displays currency options in view', function (): void {
        Livewire::actingAs($this->admin)
            ->test(Organization::class)
            ->assertSee('Ghanaian Cedi')
            ->assertSee('US Dollar');
    });

    it('handles invalid currency string gracefully', function (): void {
        $component = Livewire::actingAs($this->admin)
            ->test(Organization::class)
            ->set('currency', 'INVALID')
            ->call('saveCurrency');

        $this->tenant->refresh();
        expect($this->tenant->getCurrencyCode())->toBe('GHS');
    });
});
