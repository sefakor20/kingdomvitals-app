<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

class SystemSettingService
{
    /**
     * Get application name.
     */
    public function getAppName(): string
    {
        return (string) SystemSetting::get('name', config('app.name'));
    }

    /**
     * Get support email.
     */
    public function getSupportEmail(): ?string
    {
        return SystemSetting::get('support_email');
    }

    /**
     * Get default trial days for new tenants.
     */
    public function getDefaultTrialDays(): int
    {
        return (int) SystemSetting::get('default_trial_days', 14);
    }

    /**
     * Get default currency.
     */
    public function getCurrency(): string
    {
        return (string) SystemSetting::get('currency', 'GHS');
    }

    /**
     * Get date format.
     */
    public function getDateFormat(): string
    {
        return (string) SystemSetting::get('date_format', 'Y-m-d');
    }

    /**
     * Check if maintenance mode is enabled.
     */
    public function isMaintenanceMode(): bool
    {
        return (bool) SystemSetting::get('maintenance_mode', false);
    }

    /**
     * Get maintenance message.
     */
    public function getMaintenanceMessage(): ?string
    {
        return SystemSetting::get('maintenance_message');
    }

    /**
     * Check if a feature is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) SystemSetting::get("{$feature}_enabled", true);
    }

    /**
     * Check if donations module is enabled.
     */
    public function isDonationsEnabled(): bool
    {
        return $this->isFeatureEnabled('donations');
    }

    /**
     * Check if SMS messaging is enabled.
     */
    public function isSmsEnabled(): bool
    {
        return $this->isFeatureEnabled('sms');
    }

    /**
     * Check if member portal is enabled.
     */
    public function isMemberPortalEnabled(): bool
    {
        return $this->isFeatureEnabled('member_portal');
    }

    /**
     * Check if tenant 2FA is enabled.
     */
    public function isTenant2faEnabled(): bool
    {
        return $this->isFeatureEnabled('tenant_2fa');
    }

    /**
     * Check if tenant API access is enabled.
     */
    public function isTenantApiAccessEnabled(): bool
    {
        return $this->isFeatureEnabled('tenant_api_access');
    }

    /**
     * Get default Paystack credentials (decrypted).
     *
     * @return array{public_key: string|null, secret_key: string|null, test_mode: bool}
     */
    public function getDefaultPaystackCredentials(): array
    {
        return [
            'public_key' => SystemSetting::get('default_paystack_public_key'),
            'secret_key' => SystemSetting::get('default_paystack_secret_key'),
            'test_mode' => (bool) SystemSetting::get('default_paystack_test_mode', true),
        ];
    }

    /**
     * Get default SMS credentials (decrypted).
     *
     * @return array{api_key: string|null, sender_id: string|null}
     */
    public function getDefaultSmsCredentials(): array
    {
        return [
            'api_key' => SystemSetting::get('default_sms_api_key'),
            'sender_id' => SystemSetting::get('default_sms_sender_id'),
        ];
    }

    /**
     * Get webhook base URL.
     */
    public function getWebhookBaseUrl(): ?string
    {
        return SystemSetting::get('webhook_base_url');
    }

    /**
     * Check if default Paystack credentials are configured.
     */
    public function hasDefaultPaystackCredentials(): bool
    {
        $credentials = $this->getDefaultPaystackCredentials();

        return ! empty($credentials['public_key']) && ! empty($credentials['secret_key']);
    }

    /**
     * Check if default SMS credentials are configured.
     */
    public function hasDefaultSmsCredentials(): bool
    {
        $credentials = $this->getDefaultSmsCredentials();

        return ! empty($credentials['api_key']);
    }
}
