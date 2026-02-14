<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Settings;

use App\Enums\Currency;
use App\Models\SuperAdminActivityLog;
use App\Models\SystemSetting;
use App\Services\ImageProcessingService;
use App\Services\PaystackService;
use App\Services\TextTangoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SystemSettings extends Component
{
    use WithFileUploads;

    // Tab State
    public string $activeTab = 'application';

    // Permission flag
    public bool $canModify = false;

    // Application Settings
    public string $appName = '';

    // Platform Logo
    public TemporaryUploadedFile|string|null $platformLogo = null;

    public ?string $existingPlatformLogoUrl = null;

    public string $supportEmail = '';

    public int $defaultTrialDays = 14;

    public string $currency = 'GHS';

    // Currency Pricing Settings
    public string $pricingStrategy = 'manual';

    public string $baseCurrency = 'GHS';

    public string $exchangeRateUsdToGhs = '15.00';

    public string $dateFormat = 'Y-m-d';

    public bool $maintenanceMode = false;

    public string $maintenanceMessage = '';

    // Integration Defaults
    public string $defaultPaystackPublicKey = '';

    public string $defaultPaystackSecretKey = '';

    public bool $defaultPaystackTestMode = true;

    public string $defaultSmsApiKey = '';

    public string $defaultSmsSenderId = '';

    public string $webhookBaseUrl = '';

    public bool $hasExistingPaystackKeys = false;

    public bool $hasExistingSmsKey = false;

    public ?string $paystackTestResult = null;

    public ?string $paystackTestStatus = null;

    public ?string $smsTestResult = null;

    public ?string $smsTestStatus = null;

    // Feature Flags
    public bool $donationsEnabled = true;

    public bool $smsEnabled = true;

    public bool $memberPortalEnabled = false;

    public bool $tenant2faEnabled = true;

    public bool $tenantApiAccessEnabled = false;

    public function mount(): void
    {
        $admin = Auth::guard('superadmin')->user();

        // Check view permission
        if (! $admin->role->canViewSettings()) {
            abort(403, 'You do not have permission to view settings.');
        }

        $this->canModify = $admin->role->canModifySettings();

        // Load application settings
        $this->appName = (string) SystemSetting::get('name', config('app.name'));
        $this->supportEmail = (string) SystemSetting::get('support_email', '');
        $this->loadPlatformLogo();
        $this->defaultTrialDays = (int) SystemSetting::get('default_trial_days', 14);
        $this->currency = (string) SystemSetting::get('currency', 'GHS');
        $this->pricingStrategy = (string) SystemSetting::get('pricing_strategy', 'manual');
        $this->baseCurrency = (string) SystemSetting::get('base_currency', 'GHS');
        $this->exchangeRateUsdToGhs = (string) SystemSetting::get('exchange_rate_usd_to_ghs', '15.00');
        $this->dateFormat = (string) SystemSetting::get('date_format', 'Y-m-d');
        $this->maintenanceMode = (bool) SystemSetting::get('maintenance_mode', false);
        $this->maintenanceMessage = (string) SystemSetting::get('maintenance_message', '');

        // Load integration settings
        $this->loadIntegrationSettings();

        // Load feature flags
        $this->donationsEnabled = (bool) SystemSetting::get('donations_enabled', true);
        $this->smsEnabled = (bool) SystemSetting::get('sms_enabled', true);
        $this->memberPortalEnabled = (bool) SystemSetting::get('member_portal_enabled', false);
        $this->tenant2faEnabled = (bool) SystemSetting::get('tenant_2fa_enabled', true);
        $this->tenantApiAccessEnabled = (bool) SystemSetting::get('tenant_api_access_enabled', false);
    }

    protected function loadIntegrationSettings(): void
    {
        // Load Paystack defaults
        $existingPublicKey = SystemSetting::get('default_paystack_public_key');
        $existingSecretKey = SystemSetting::get('default_paystack_secret_key');
        $this->hasExistingPaystackKeys = ! empty($existingPublicKey) && ! empty($existingSecretKey);

        if (! empty($existingPublicKey)) {
            $this->defaultPaystackPublicKey = $this->maskCredential((string) $existingPublicKey, 8);
        }

        if (! empty($existingSecretKey)) {
            $this->defaultPaystackSecretKey = $this->maskCredential((string) $existingSecretKey, 8);
        }

        $this->defaultPaystackTestMode = (bool) SystemSetting::get('default_paystack_test_mode', true);

        // Load SMS defaults
        $existingSmsKey = SystemSetting::get('default_sms_api_key');
        $this->hasExistingSmsKey = ! empty($existingSmsKey);

        if (! empty($existingSmsKey)) {
            $this->defaultSmsApiKey = $this->maskCredential((string) $existingSmsKey, 4);
        }

        $this->defaultSmsSenderId = (string) SystemSetting::get('default_sms_sender_id', '');
        $this->webhookBaseUrl = (string) SystemSetting::get('webhook_base_url', '');
    }

    protected function maskCredential(string $value, int $visibleChars): string
    {
        $length = strlen($value);
        if ($length <= $visibleChars) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', $length - $visibleChars).substr($value, -$visibleChars);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTestResults();
    }

    protected function resetTestResults(): void
    {
        $this->paystackTestResult = null;
        $this->paystackTestStatus = null;
        $this->smsTestResult = null;
        $this->smsTestStatus = null;
    }

    protected function ensureCanModify(): void
    {
        if (! $this->canModify) {
            abort(403, 'You do not have permission to modify settings.');
        }
    }

    public function saveApplicationSettings(): void
    {
        $this->ensureCanModify();

        $this->validate([
            'appName' => ['required', 'string', 'max:100'],
            'supportEmail' => ['nullable', 'email', 'max:255'],
            'defaultTrialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'currency' => ['required', 'string', 'max:10'],
            'pricingStrategy' => ['required', 'in:manual,exchange_rate'],
            'baseCurrency' => ['required', 'in:GHS,USD'],
            'exchangeRateUsdToGhs' => ['required', 'numeric', 'min:0.01'],
            'dateFormat' => ['required', 'string', 'max:20'],
            'maintenanceMessage' => ['nullable', 'string', 'max:500'],
        ]);

        SystemSetting::set('name', $this->appName, 'app');
        SystemSetting::set('support_email', $this->supportEmail ?: null, 'app');
        SystemSetting::set('default_trial_days', (string) $this->defaultTrialDays, 'app');
        SystemSetting::set('currency', $this->currency, 'app');
        SystemSetting::set('pricing_strategy', $this->pricingStrategy, 'currency');
        SystemSetting::set('base_currency', $this->baseCurrency, 'currency');
        SystemSetting::set('exchange_rate_usd_to_ghs', $this->exchangeRateUsdToGhs, 'currency');
        SystemSetting::set('date_format', $this->dateFormat, 'app');
        SystemSetting::set('maintenance_mode', $this->maintenanceMode, 'app');
        SystemSetting::set('maintenance_message', $this->maintenanceMessage ?: null, 'app');

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Updated application settings',
            metadata: [
                'section' => 'application',
                'app_name' => $this->appName,
                'maintenance_mode' => $this->maintenanceMode,
                'pricing_strategy' => $this->pricingStrategy,
                'base_currency' => $this->baseCurrency,
            ],
        );

        $this->dispatch('settings-saved');
    }

    public function saveIntegrationSettings(): void
    {
        $this->ensureCanModify();

        $this->validate([
            'defaultSmsSenderId' => ['nullable', 'string', 'max:11'],
            'webhookBaseUrl' => ['nullable', 'url', 'max:255'],
        ]);

        // Save Paystack keys (only if new values entered, not masked)
        if ($this->defaultPaystackPublicKey && ! str_contains($this->defaultPaystackPublicKey, '•')) {
            SystemSetting::set('default_paystack_public_key', $this->defaultPaystackPublicKey, 'integrations', true);
        }

        if ($this->defaultPaystackSecretKey && ! str_contains($this->defaultPaystackSecretKey, '•')) {
            SystemSetting::set('default_paystack_secret_key', $this->defaultPaystackSecretKey, 'integrations', true);
        }

        SystemSetting::set('default_paystack_test_mode', $this->defaultPaystackTestMode, 'integrations');

        // Save SMS keys (only if new values entered, not masked)
        if ($this->defaultSmsApiKey && ! str_contains($this->defaultSmsApiKey, '•')) {
            SystemSetting::set('default_sms_api_key', $this->defaultSmsApiKey, 'integrations', true);
        }

        SystemSetting::set('default_sms_sender_id', $this->defaultSmsSenderId ?: null, 'integrations');
        SystemSetting::set('webhook_base_url', $this->webhookBaseUrl ?: null, 'integrations');

        // Mask credentials after saving
        if ($this->defaultPaystackPublicKey && ! str_contains($this->defaultPaystackPublicKey, '•')) {
            $this->defaultPaystackPublicKey = $this->maskCredential($this->defaultPaystackPublicKey, 8);
        }

        if ($this->defaultPaystackSecretKey && ! str_contains($this->defaultPaystackSecretKey, '•')) {
            $this->defaultPaystackSecretKey = $this->maskCredential($this->defaultPaystackSecretKey, 8);
        }

        if ($this->defaultSmsApiKey && ! str_contains($this->defaultSmsApiKey, '•')) {
            $this->defaultSmsApiKey = $this->maskCredential($this->defaultSmsApiKey, 4);
        }

        $this->hasExistingPaystackKeys = ! empty(SystemSetting::get('default_paystack_public_key'))
            && ! empty(SystemSetting::get('default_paystack_secret_key'));
        $this->hasExistingSmsKey = ! empty(SystemSetting::get('default_sms_api_key'));

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Updated integration settings',
            metadata: [
                'section' => 'integrations',
                'paystack_configured' => $this->hasExistingPaystackKeys,
                'sms_configured' => $this->hasExistingSmsKey,
            ],
        );

        $this->dispatch('settings-saved');
    }

    public function saveFeatureSettings(): void
    {
        $this->ensureCanModify();

        SystemSetting::set('donations_enabled', $this->donationsEnabled, 'features');
        SystemSetting::set('sms_enabled', $this->smsEnabled, 'features');
        SystemSetting::set('member_portal_enabled', $this->memberPortalEnabled, 'features');
        SystemSetting::set('tenant_2fa_enabled', $this->tenant2faEnabled, 'features');
        SystemSetting::set('tenant_api_access_enabled', $this->tenantApiAccessEnabled, 'features');

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Updated feature flags',
            metadata: [
                'section' => 'features',
                'donations_enabled' => $this->donationsEnabled,
                'sms_enabled' => $this->smsEnabled,
                'member_portal_enabled' => $this->memberPortalEnabled,
                'tenant_2fa_enabled' => $this->tenant2faEnabled,
                'tenant_api_access_enabled' => $this->tenantApiAccessEnabled,
            ],
        );

        $this->dispatch('settings-saved');
    }

    public function testPaystackConnection(): void
    {
        $this->ensureCanModify();

        $this->paystackTestResult = null;
        $this->paystackTestStatus = null;

        $publicKey = $this->getCredentialForTesting('default_paystack_public_key', $this->defaultPaystackPublicKey);
        $secretKey = $this->getCredentialForTesting('default_paystack_secret_key', $this->defaultPaystackSecretKey);

        if (in_array($publicKey, [null, '', '0'], true) || in_array($secretKey, [null, '', '0'], true)) {
            $this->paystackTestResult = __('Please enter both Public Key and Secret Key first.');
            $this->paystackTestStatus = 'error';

            return;
        }

        $service = new PaystackService($secretKey, $publicKey, $this->defaultPaystackTestMode);
        $result = $service->verifyTransaction('test-connection-'.time());

        // A "Transaction reference not found" error means the API is working
        if (! $result['success'] && str_contains($result['error'] ?? '', 'not found')) {
            $this->paystackTestResult = __('Connection successful! Paystack API is working.');
            $this->paystackTestStatus = 'success';
        } elseif (! $result['success']) {
            if (str_contains($result['error'] ?? '', 'Invalid key') || str_contains($result['error'] ?? '', 'Unauthorized')) {
                $this->paystackTestResult = __('Invalid API keys. Please check your credentials.');
            } else {
                $this->paystackTestResult = __('Connection test completed. Error: :error', [
                    'error' => $result['error'] ?? 'Unknown',
                ]);
            }
            $this->paystackTestStatus = 'error';
        } else {
            $this->paystackTestResult = __('Connection successful!');
            $this->paystackTestStatus = 'success';
        }
    }

    public function testSmsConnection(): void
    {
        $this->ensureCanModify();

        $this->smsTestResult = null;
        $this->smsTestStatus = null;

        $apiKey = $this->getCredentialForTesting('default_sms_api_key', $this->defaultSmsApiKey);

        if (in_array($apiKey, [null, '', '0'], true)) {
            $this->smsTestResult = __('Please enter an API key first.');
            $this->smsTestStatus = 'error';

            return;
        }

        $senderId = $this->defaultSmsSenderId ?: (string) SystemSetting::get('default_sms_sender_id');

        if ($senderId === '' || $senderId === '0') {
            $this->smsTestResult = __('Please enter a Sender ID first.');
            $this->smsTestStatus = 'error';

            return;
        }

        $service = new TextTangoService($apiKey, $senderId);
        $result = $service->getBalance();

        if ($result['success']) {
            $this->smsTestResult = __('Connection successful! Balance: :currency :balance', [
                'currency' => $result['currency'] ?? 'GHS',
                'balance' => number_format($result['balance'] ?? 0, 2),
            ]);
            $this->smsTestStatus = 'success';
        } else {
            $this->smsTestResult = __('Connection failed: :error', [
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            $this->smsTestStatus = 'error';
        }
    }

    protected function getCredentialForTesting(string $settingKey, string $formValue): ?string
    {
        if ($formValue && ! str_contains($formValue, '•')) {
            return $formValue;
        }

        $existingValue = SystemSetting::get($settingKey);
        if ($existingValue) {
            return (string) $existingValue;
        }

        return null;
    }

    public function clearPaystackKeys(): void
    {
        $this->ensureCanModify();

        SystemSetting::remove('default_paystack_public_key');
        SystemSetting::remove('default_paystack_secret_key');

        $this->defaultPaystackPublicKey = '';
        $this->defaultPaystackSecretKey = '';
        $this->hasExistingPaystackKeys = false;

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Cleared default Paystack credentials',
            metadata: ['section' => 'integrations', 'action' => 'clear_paystack'],
        );

        $this->dispatch('credentials-cleared');
    }

    public function clearSmsKey(): void
    {
        $this->ensureCanModify();

        SystemSetting::remove('default_sms_api_key');

        $this->defaultSmsApiKey = '';
        $this->hasExistingSmsKey = false;

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Cleared default SMS API key',
            metadata: ['section' => 'integrations', 'action' => 'clear_sms'],
        );

        $this->dispatch('credentials-cleared');
    }

    protected function loadPlatformLogo(): void
    {
        $logoPaths = SystemSetting::get('platform_logo');

        if ($logoPaths && is_array($logoPaths)) {
            $imageService = app(ImageProcessingService::class);
            $this->existingPlatformLogoUrl = $imageService->getLogoUrl($logoPaths, 'medium');
        }
    }

    public function savePlatformLogo(): void
    {
        $this->ensureCanModify();

        if (! $this->platformLogo instanceof TemporaryUploadedFile) {
            return;
        }

        $imageService = app(ImageProcessingService::class);

        // Validate the logo
        $errors = $imageService->validateLogo($this->platformLogo);
        if (! empty($errors)) {
            foreach ($errors as $message) {
                $this->addError('platformLogo', $message);
            }

            return;
        }

        // Delete existing logo if present
        $existingPaths = SystemSetting::get('platform_logo');
        if ($existingPaths && is_array($existingPaths)) {
            $imageService->deleteLogoByPaths($existingPaths);
        }

        // Process and store the new logo
        $paths = $imageService->processLogo($this->platformLogo, 'logos/platform');

        // Save paths to system settings
        SystemSetting::set('platform_logo', $paths, 'app');

        // Update URL for display
        $this->existingPlatformLogoUrl = $imageService->getLogoUrl($paths, 'medium');
        $this->platformLogo = null;

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Updated platform logo',
            metadata: ['section' => 'application', 'action' => 'upload_logo'],
        );

        $this->dispatch('logo-saved');
    }

    public function removePlatformLogo(): void
    {
        $this->ensureCanModify();

        $existingPaths = SystemSetting::get('platform_logo');

        if ($existingPaths && is_array($existingPaths)) {
            $imageService = app(ImageProcessingService::class);
            $imageService->deleteLogoByPaths($existingPaths);
        }

        SystemSetting::remove('platform_logo');
        $this->existingPlatformLogoUrl = null;
        $this->platformLogo = null;

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'settings_updated',
            description: 'Removed platform logo',
            metadata: ['section' => 'application', 'action' => 'remove_logo'],
        );

        $this->dispatch('logo-removed');
    }

    public function render(): View
    {
        return view('livewire.super-admin.settings.system-settings', [
            'currencies' => Currency::cases(),
        ])->layout('components.layouts.superadmin.app');
    }
}
