<div>
    <!-- Header -->
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Security Settings') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">
            {{ __('Manage your account security and two-factor authentication.') }}
        </flux:text>
    </div>

    <!-- Two-Factor Authentication Section -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Two-Factor Authentication') }}</flux:heading>
        </div>
        <div class="p-6" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <flux:badge color="green">{{ __('Enabled') }}</flux:badge>
                    </div>

                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from a TOTP-supported application on your phone.') }}
                    </flux:text>

                    <!-- Recovery Codes Section -->
                    @if(count($recoveryCodes) > 0)
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4" x-data="{ showCodes: false }">
                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">{{ __('Recovery Codes') }}</flux:heading>
                                <div class="flex items-center gap-2">
                                    <flux:button
                                        @click="showCodes = !showCodes"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <span x-show="!showCodes">{{ __('Show Codes') }}</span>
                                        <span x-show="showCodes" x-cloak>{{ __('Hide Codes') }}</span>
                                    </flux:button>
                                    <flux:button
                                        wire:click="regenerateRecoveryCodes"
                                        variant="ghost"
                                        size="sm"
                                        wire:confirm="Are you sure you want to regenerate your recovery codes? This will invalidate your existing codes."
                                    >
                                        {{ __('Regenerate') }}
                                    </flux:button>
                                </div>
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                {{ __('Store these recovery codes in a secure location. They can be used to access your account if you lose your authenticator device.') }}
                            </flux:text>

                            <div x-show="showCodes" x-collapse x-cloak class="grid grid-cols-2 gap-2 font-mono text-sm">
                                @foreach($recoveryCodes as $code)
                                    <div class="rounded bg-zinc-100 dark:bg-zinc-700 px-3 py-2">
                                        {{ $code }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-start">
                        <flux:button
                            variant="danger"
                            icon="shield-exclamation"
                            wire:click="disable"
                            wire:confirm="Are you sure you want to disable two-factor authentication? This will make your account less secure."
                        >
                            {{ __('Disable 2FA') }}
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <flux:badge color="red">{{ __('Disabled') }}</flux:badge>
                    </div>

                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone, such as Google Authenticator or Authy.') }}
                    </flux:text>

                    <flux:button
                        variant="primary"
                        icon="shield-check"
                        wire:click="enable"
                    >
                        {{ __('Enable 2FA') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    <!-- Setup Modal -->
    <flux:modal wire:model="showModal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon.qr-code class="size-8 text-indigo-600 dark:text-indigo-400" />
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $this->modalConfig['description'] }}</flux:text>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3 justify-center">
                        <flux:otp
                            name="code"
                            wire:model="code"
                            length="6"
                            label="OTP Code"
                            label:sr-only
                            class="mx-auto"
                        />

                        @error('code')
                            <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button
                            variant="ghost"
                            class="flex-1"
                            wire:click="resetVerification"
                        >
                            {{ __('Back') }}
                        </flux:button>

                        <flux:button
                            variant="primary"
                            class="flex-1"
                            wire:click="confirmTwoFactor"
                        >
                            {{ __('Confirm') }}
                        </flux:button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-zinc-700 animate-pulse">
                                <flux:icon.loading />
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4">
                                <div class="bg-white p-3 rounded">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <flux:button
                        :disabled="$errors->has('setupData')"
                        variant="primary"
                        class="w-full"
                        wire:click="showVerificationIfNecessary"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </flux:button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-zinc-200 dark:bg-zinc-600"></div>
                        <span class="relative px-2 text-sm bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl dark:border-zinc-700">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-zinc-100 dark:bg-zinc-700">
                                    <flux:icon.loading variant="mini"/>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none text-zinc-900 dark:text-zinc-100 text-sm font-mono"
                                />

                                <button
                                    @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-zinc-200 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" class="size-5"/>
                                    <flux:icon.check
                                        x-show="copied"
                                        variant="solid"
                                        class="size-5 text-green-500"
                                    />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>

    <!-- Toast Notifications -->
    <x-toast on="two-factor-enabled" type="success">
        {{ __('Two-factor authentication enabled successfully.') }}
    </x-toast>
    <x-toast on="two-factor-disabled" type="success">
        {{ __('Two-factor authentication disabled.') }}
    </x-toast>
    <x-toast on="recovery-codes-regenerated" type="success">
        {{ __('Recovery codes regenerated successfully.') }}
    </x-toast>
</div>
