<?php

declare(strict_types=1);

use App\Enums\AiAlertType;
use App\Enums\BranchRole;
use App\Livewire\Branches\AlertSettings;
use App\Models\Tenant\AiAlertSetting;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('can view alert settings page', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('AI Alert Settings');
});

it('loads all alert types on mount', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->assertSet('settings.'.AiAlertType::ChurnRisk->value.'.is_enabled', true)
        ->assertSet('settings.'.AiAlertType::AttendanceAnomaly->value.'.is_enabled', true)
        ->assertSet('settings.'.AiAlertType::LifecycleChange->value.'.is_enabled', true)
        ->assertSet('settings.'.AiAlertType::CriticalPrayer->value.'.is_enabled', true)
        ->assertSet('settings.'.AiAlertType::ClusterHealth->value.'.is_enabled', true)
        ->assertSet('settings.'.AiAlertType::HouseholdDisengagement->value.'.is_enabled', true);
});

it('can toggle alert enabled state', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->set('settings.'.AiAlertType::ChurnRisk->value.'.is_enabled', false)
        ->call('save');

    $setting = AiAlertSetting::forBranch($this->branch->id)
        ->ofType(AiAlertType::ChurnRisk)
        ->first();

    expect($setting->is_enabled)->toBeFalse();
});

it('can update threshold value', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->set('settings.'.AiAlertType::ChurnRisk->value.'.threshold_value', 80)
        ->call('save');

    $setting = AiAlertSetting::forBranch($this->branch->id)
        ->ofType(AiAlertType::ChurnRisk)
        ->first();

    expect($setting->threshold_value)->toBe(80);
});

it('can update cooldown hours', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->set('settings.'.AiAlertType::ChurnRisk->value.'.cooldown_hours', 24)
        ->call('save');

    $setting = AiAlertSetting::forBranch($this->branch->id)
        ->ofType(AiAlertType::ChurnRisk)
        ->first();

    expect($setting->cooldown_hours)->toBe(24);
});

it('can toggle notification channels', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch]);

    // Initially has database and mail
    $initialChannels = $component->get('settings.'.AiAlertType::ChurnRisk->value.'.notification_channels');
    expect($initialChannels)->toContain('database');

    // Toggle sms on
    $component->call('toggleChannel', AiAlertType::ChurnRisk->value, 'sms');

    $newChannels = $component->get('settings.'.AiAlertType::ChurnRisk->value.'.notification_channels');
    expect($newChannels)->toContain('sms');

    // Toggle sms off
    $component->call('toggleChannel', AiAlertType::ChurnRisk->value, 'sms');

    $finalChannels = $component->get('settings.'.AiAlertType::ChurnRisk->value.'.notification_channels');
    expect($finalChannels)->not->toContain('sms');
});

it('can toggle recipient roles', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch]);

    // Toggle staff on
    $component->call('toggleRole', AiAlertType::ChurnRisk->value, 'staff');

    $roles = $component->get('settings.'.AiAlertType::ChurnRisk->value.'.recipient_roles');
    expect($roles)->toContain('staff');

    // Toggle staff off
    $component->call('toggleRole', AiAlertType::ChurnRisk->value, 'staff');

    $finalRoles = $component->get('settings.'.AiAlertType::ChurnRisk->value.'.recipient_roles');
    expect($finalRoles)->not->toContain('staff');
});

it('can reset alert to defaults', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->set('settings.'.AiAlertType::ChurnRisk->value.'.threshold_value', 95)
        ->set('settings.'.AiAlertType::ChurnRisk->value.'.cooldown_hours', 1)
        ->call('resetToDefaults', AiAlertType::ChurnRisk->value)
        ->assertSet('settings.'.AiAlertType::ChurnRisk->value.'.threshold_value', 70)
        ->assertSet('settings.'.AiAlertType::ChurnRisk->value.'.cooldown_hours', 168);
});

it('persists settings after save', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->set('settings.'.AiAlertType::AttendanceAnomaly->value.'.threshold_value', 60)
        ->set('settings.'.AiAlertType::AttendanceAnomaly->value.'.cooldown_hours', 72)
        ->call('save');

    // Reload the component
    $component = Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch]);

    expect($component->get('settings.'.AiAlertType::AttendanceAnomaly->value.'.threshold_value'))->toBe(60);
    expect($component->get('settings.'.AiAlertType::AttendanceAnomaly->value.'.cooldown_hours'))->toBe(72);
});

it('dispatches settings-saved event on save', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->call('save')
        ->assertDispatched('settings-saved');
});

it('can expand and collapse alert cards', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->assertSet('expanded.'.AiAlertType::ChurnRisk->value, false);

    $component->call('toggleExpanded', AiAlertType::ChurnRisk->value)
        ->assertSet('expanded.'.AiAlertType::ChurnRisk->value, true);

    $component->call('toggleExpanded', AiAlertType::ChurnRisk->value)
        ->assertSet('expanded.'.AiAlertType::ChurnRisk->value, false);
});

it('unauthorized users cannot access settings', function (): void {
    $unauthorizedUser = User::factory()->create();

    Livewire::actingAs($unauthorizedUser)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->assertForbidden();
});

it('shows all six alert types', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->assertSee('Churn Risk Alert')
        ->assertSee('Attendance Anomaly')
        ->assertSee('Lifecycle Transition')
        ->assertSee('Critical Prayer Request')
        ->assertSee('Cluster Health Alert')
        ->assertSee('Household Disengagement');
});

it('saves notification channels correctly', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->call('toggleChannel', AiAlertType::ChurnRisk->value, 'sms')
        ->call('save');

    $setting = AiAlertSetting::forBranch($this->branch->id)
        ->ofType(AiAlertType::ChurnRisk)
        ->first();

    expect($setting->notification_channels)->toContain('sms');
});

it('saves recipient roles correctly', function (): void {
    Livewire::actingAs($this->user)
        ->test(AlertSettings::class, ['branch' => $this->branch])
        ->call('toggleRole', AiAlertType::ChurnRisk->value, 'leader')
        ->call('save');

    $setting = AiAlertSetting::forBranch($this->branch->id)
        ->ofType(AiAlertType::ChurnRisk)
        ->first();

    expect($setting->recipient_roles)->toContain('leader');
});
