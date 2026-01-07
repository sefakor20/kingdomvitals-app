<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Profile;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Security extends Component
{
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    #[Locked]
    public array $recoveryCodes = [];

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    public bool $showRecoveryCodesModal = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $admin = Auth::guard('superadmin')->user();

        // If 2FA is enabled but not confirmed, disable it (clean up partial setup)
        if ($admin->two_factor_secret && is_null($admin->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication($admin);
        }

        $this->twoFactorEnabled = $admin->hasEnabledTwoFactorAuthentication();
        $this->loadRecoveryCodes();
    }

    /**
     * Enable two-factor authentication for the admin.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $admin = Auth::guard('superadmin')->user();
        $enableTwoFactorAuthentication($admin);

        $this->loadSetupData();
        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data.
     */
    private function loadSetupData(): void
    {
        $admin = Auth::guard('superadmin')->user();

        try {
            $this->qrCodeSvg = $admin->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($admin->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');
            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the verification step.
     */
    public function showVerificationIfNecessary(): void
    {
        $this->showVerificationStep = true;
        $this->resetErrorBag();
    }

    /**
     * Confirm two-factor authentication.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $admin = Auth::guard('superadmin')->user();
        $confirmTwoFactorAuthentication($admin, $this->code);

        $this->closeModal();
        $this->twoFactorEnabled = true;
        $this->loadRecoveryCodes();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset the verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $admin = Auth::guard('superadmin')->user();
        $disableTwoFactorAuthentication($admin);

        $this->twoFactorEnabled = false;
        $this->recoveryCodes = [];

        $this->dispatch('two-factor-disabled');
    }

    /**
     * Close the setup modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();
    }

    /**
     * Load recovery codes.
     */
    private function loadRecoveryCodes(): void
    {
        $admin = Auth::guard('superadmin')->user();

        if ($admin->hasEnabledTwoFactorAuthentication() && $admin->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($admin->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->recoveryCodes = [];
            }
        }
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $admin = Auth::guard('superadmin')->user();
        $generateNewRecoveryCodes($admin);

        $this->loadRecoveryCodes();
        $this->dispatch('recovery-codes-regenerated');
    }

    /**
     * Get the modal configuration.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-Factor Authentication Enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify Authentication Code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable Two-Factor Authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.super-admin.profile.security')
            ->layout('components.layouts.superadmin.app');
    }
}
