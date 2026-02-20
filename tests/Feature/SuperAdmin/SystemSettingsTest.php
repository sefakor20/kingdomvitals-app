<?php

declare(strict_types=1);

use App\Enums\SuperAdminRole;
use App\Livewire\SuperAdmin\Settings\SystemSettings;
use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use Livewire\Livewire;

describe('access control', function (): void {
    it('allows owner to view settings page', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        $this->actingAs($owner, 'superadmin')
            ->get(route('superadmin.settings'))
            ->assertOk()
            ->assertSee('System Settings');
    });

    it('allows admin to view settings page', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.settings'))
            ->assertOk()
            ->assertSee('System Settings');
    });

    it('denies support role access to settings page', function (): void {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        $this->actingAs($support, 'superadmin')
            ->get(route('superadmin.settings'))
            ->assertForbidden();
    });

    it('denies guest access to settings page', function (): void {
        $this->get(route('superadmin.settings'))
            ->assertRedirect(route('superadmin.login'));
    });

    it('shows canModify as true for owner', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('canModify', true);
    });

    it('shows canModify as false for admin', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('canModify', false);
    });
});

describe('tab navigation', function (): void {
    it('defaults to application tab', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('activeTab', 'application');
    });

    it('can switch tabs', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->call('setActiveTab', 'integrations')
            ->assertSet('activeTab', 'integrations')
            ->call('setActiveTab', 'features')
            ->assertSet('activeTab', 'features')
            ->call('setActiveTab', 'application')
            ->assertSet('activeTab', 'application');
    });
});

describe('application settings', function (): void {
    it('loads default values on mount', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('defaultTrialDays', 14)
            ->assertSet('currency', 'GHS')
            ->assertSet('dateFormat', 'Y-m-d')
            ->assertSet('maintenanceMode', false);
    });

    it('loads existing settings on mount', function (): void {
        SystemSetting::set('name', 'Test App');
        SystemSetting::set('support_email', 'support@test.com');
        SystemSetting::set('default_trial_days', '30');
        SystemSetting::set('currency', 'USD');

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('appName', 'Test App')
            ->assertSet('supportEmail', 'support@test.com')
            ->assertSet('defaultTrialDays', 30)
            ->assertSet('currency', 'USD');
    });

    it('owner can save application settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('appName', 'New App Name')
            ->set('supportEmail', 'new@support.com')
            ->set('defaultTrialDays', 30)
            ->set('currency', 'USD')
            ->set('dateFormat', 'd/m/Y')
            ->set('maintenanceMode', true)
            ->set('maintenanceMessage', 'Under maintenance')
            ->call('saveApplicationSettings')
            ->assertDispatched('settings-saved');

        expect(SystemSetting::get('name'))->toBe('New App Name');
        expect(SystemSetting::get('support_email'))->toBe('new@support.com');
        expect(SystemSetting::get('default_trial_days'))->toBe('30');
        expect(SystemSetting::get('currency'))->toBe('USD');
        expect(SystemSetting::get('date_format'))->toBe('d/m/Y');
        expect(SystemSetting::get('maintenance_mode'))->toBeTrue();
        expect(SystemSetting::get('maintenance_message'))->toBe('Under maintenance');
    });

    it('admin cannot save application settings', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(SystemSettings::class)
            ->set('appName', 'Changed Name')
            ->call('saveApplicationSettings')
            ->assertForbidden();
    });

    it('validates required fields in application settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('appName', '')
            ->set('defaultTrialDays', -1)
            ->call('saveApplicationSettings')
            ->assertHasErrors(['appName', 'defaultTrialDays']);
    });

    it('validates email format for support email', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('appName', 'Test')
            ->set('supportEmail', 'invalid-email')
            ->call('saveApplicationSettings')
            ->assertHasErrors(['supportEmail']);
    });

    it('logs activity when saving application settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('appName', 'Logged App')
            ->call('saveApplicationSettings');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'settings_updated',
        ]);
    });
});

describe('integration settings', function (): void {
    it('can save integration settings with new credentials', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('defaultPaystackPublicKey', 'pk_test_123456789')
            ->set('defaultPaystackSecretKey', 'sk_test_987654321')
            ->set('defaultPaystackTestMode', true)
            ->set('defaultSmsApiKey', 'sms_key_12345')
            ->set('defaultSmsSenderId', 'TestApp')
            ->set('webhookBaseUrl', 'https://example.com/webhooks')
            ->call('saveIntegrationSettings')
            ->assertDispatched('settings-saved');

        expect(SystemSetting::get('default_paystack_public_key'))->toBe('pk_test_123456789');
        expect(SystemSetting::get('default_paystack_secret_key'))->toBe('sk_test_987654321');
        expect(SystemSetting::get('default_paystack_test_mode'))->toBeTrue();
        expect(SystemSetting::get('default_sms_api_key'))->toBe('sms_key_12345');
        expect(SystemSetting::get('default_sms_sender_id'))->toBe('TestApp');
        expect(SystemSetting::get('webhook_base_url'))->toBe('https://example.com/webhooks');
    });

    it('masks existing credentials on load', function (): void {
        SystemSetting::set('default_paystack_public_key', 'pk_test_12345678', 'integrations', true);
        SystemSetting::set('default_paystack_secret_key', 'sk_test_87654321', 'integrations', true);
        SystemSetting::set('default_sms_api_key', 'sms_api_key_test', 'integrations', true);

        $owner = SuperAdmin::factory()->owner()->create();

        $component = Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class);

        expect($component->get('defaultPaystackPublicKey'))->toContain('•');
        expect($component->get('defaultPaystackPublicKey'))->toContain('12345678');
        expect($component->get('defaultPaystackSecretKey'))->toContain('•');
        expect($component->get('defaultSmsApiKey'))->toContain('•');
    });

    it('does not save masked credentials', function (): void {
        SystemSetting::set('default_paystack_public_key', 'pk_original', 'integrations', true);

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('defaultPaystackPublicKey', '••••••iginal')
            ->call('saveIntegrationSettings');

        expect(SystemSetting::get('default_paystack_public_key'))->toBe('pk_original');
    });

    it('admin cannot save integration settings', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(SystemSettings::class)
            ->set('defaultSmsSenderId', 'Admin')
            ->call('saveIntegrationSettings')
            ->assertForbidden();
    });

    it('validates webhook base url format', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('webhookBaseUrl', 'not-a-url')
            ->call('saveIntegrationSettings')
            ->assertHasErrors(['webhookBaseUrl']);
    });

    it('validates sender id max length', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('defaultSmsSenderId', 'TooLongSenderId')
            ->call('saveIntegrationSettings')
            ->assertHasErrors(['defaultSmsSenderId']);
    });

    it('can clear paystack keys', function (): void {
        SystemSetting::set('default_paystack_public_key', 'pk_test', 'integrations', true);
        SystemSetting::set('default_paystack_secret_key', 'sk_test', 'integrations', true);

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->call('clearPaystackKeys')
            ->assertSet('defaultPaystackPublicKey', '')
            ->assertSet('defaultPaystackSecretKey', '')
            ->assertSet('hasExistingPaystackKeys', false)
            ->assertDispatched('credentials-cleared');

        expect(SystemSetting::get('default_paystack_public_key'))->toBeNull();
        expect(SystemSetting::get('default_paystack_secret_key'))->toBeNull();
    });

    it('can clear sms key', function (): void {
        SystemSetting::set('default_sms_api_key', 'sms_key', 'integrations', true);

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->call('clearSmsKey')
            ->assertSet('defaultSmsApiKey', '')
            ->assertSet('hasExistingSmsKey', false)
            ->assertDispatched('credentials-cleared');

        expect(SystemSetting::get('default_sms_api_key'))->toBeNull();
    });

    it('logs activity when saving integration settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('defaultSmsSenderId', 'Sender')
            ->call('saveIntegrationSettings');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'settings_updated',
        ]);
    });

    it('logs activity when clearing paystack keys', function (): void {
        SystemSetting::set('default_paystack_public_key', 'pk', 'integrations', true);
        SystemSetting::set('default_paystack_secret_key', 'sk', 'integrations', true);

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->call('clearPaystackKeys');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'settings_updated',
        ]);
    });
});

describe('feature settings', function (): void {
    it('loads default feature flags on mount', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('donationsEnabled', true)
            ->assertSet('smsEnabled', true)
            ->assertSet('memberPortalEnabled', false)
            ->assertSet('tenant2faEnabled', true)
            ->assertSet('tenantApiAccessEnabled', false);
    });

    it('loads existing feature flags on mount', function (): void {
        SystemSetting::set('donations_enabled', false, 'features');
        SystemSetting::set('member_portal_enabled', true, 'features');

        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->assertSet('donationsEnabled', false)
            ->assertSet('memberPortalEnabled', true);
    });

    it('owner can save feature settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('donationsEnabled', false)
            ->set('smsEnabled', false)
            ->set('memberPortalEnabled', true)
            ->set('tenant2faEnabled', false)
            ->set('tenantApiAccessEnabled', true)
            ->call('saveFeatureSettings')
            ->assertDispatched('settings-saved');

        expect(SystemSetting::get('donations_enabled'))->toBeFalse();
        expect(SystemSetting::get('sms_enabled'))->toBeFalse();
        expect(SystemSetting::get('member_portal_enabled'))->toBeTrue();
        expect(SystemSetting::get('tenant_2fa_enabled'))->toBeFalse();
        expect(SystemSetting::get('tenant_api_access_enabled'))->toBeTrue();
    });

    it('admin cannot save feature settings', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(SystemSettings::class)
            ->set('donationsEnabled', false)
            ->call('saveFeatureSettings')
            ->assertForbidden();
    });

    it('logs activity when saving feature settings', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(SystemSettings::class)
            ->set('smsEnabled', false)
            ->call('saveFeatureSettings');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'settings_updated',
        ]);
    });
});

describe('SystemSetting model', function (): void {
    it('can get and set values', function (): void {
        SystemSetting::set('test_key', 'test_value');

        expect(SystemSetting::get('test_key'))->toBe('test_value');
    });

    it('returns default when key not found', function (): void {
        expect(SystemSetting::get('nonexistent', 'default_value'))->toBe('default_value');
    });

    it('encrypts values when specified', function (): void {
        SystemSetting::set('secret_key', 'secret_value', 'test', true);

        $setting = SystemSetting::where('key', 'secret_key')->first();
        expect($setting->is_encrypted)->toBeTrue();
        expect($setting->value)->not->toBe('secret_value');

        // But get() should decrypt it
        expect(SystemSetting::get('secret_key'))->toBe('secret_value');
    });

    it('handles boolean values correctly', function (): void {
        SystemSetting::set('bool_true', true);
        SystemSetting::set('bool_false', false);

        expect(SystemSetting::get('bool_true'))->toBeTrue();
        expect(SystemSetting::get('bool_false'))->toBeFalse();
    });

    it('can remove settings', function (): void {
        SystemSetting::set('removable', 'value');
        expect(SystemSetting::get('removable'))->toBe('value');

        SystemSetting::remove('removable');
        expect(SystemSetting::get('removable'))->toBeNull();
    });

    it('can get all settings in a group', function (): void {
        SystemSetting::set('key1', 'value1', 'test_group');
        SystemSetting::set('key2', 'value2', 'test_group');
        SystemSetting::set('key3', 'value3', 'other_group');

        $settings = SystemSetting::getGroup('test_group');

        expect($settings)->toHaveCount(2);
        expect($settings['key1'])->toBe('value1');
        expect($settings['key2'])->toBe('value2');
        expect($settings)->not->toHaveKey('key3');
    });
});
