<x-layouts.superadmin.auth>
    <div
        class="flex flex-col gap-6"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                this.code = '';
                this.recovery_code = '';
            },
        }"
    >
        <div x-show="!showRecoveryInput">
            <div class="flex flex-col gap-2 text-center mb-6">
                <flux:heading size="lg" class="text-white">{{ __('Authentication Code') }}</flux:heading>
                <flux:text class="text-zinc-400">
                    {{ __('Enter the authentication code provided by your authenticator application.') }}
                </flux:text>
            </div>
        </div>

        <div x-show="showRecoveryInput">
            <div class="flex flex-col gap-2 text-center mb-6">
                <flux:heading size="lg" class="text-white">{{ __('Recovery Code') }}</flux:heading>
                <flux:text class="text-zinc-400">
                    {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
                </flux:text>
            </div>
        </div>

        <form method="POST" action="{{ route('superadmin.two-factor.challenge') }}">
            @csrf

            <div class="space-y-5 text-center">
                <div x-show="!showRecoveryInput">
                    <div class="flex items-center justify-center my-5">
                        <flux:otp
                            x-model="code"
                            length="6"
                            name="code"
                            label="OTP Code"
                            label:sr-only
                            class="mx-auto"
                        />
                    </div>

                    @error('code')
                        <flux:text class="text-red-400 text-sm">
                            {{ $message }}
                        </flux:text>
                    @enderror
                </div>

                <div x-show="showRecoveryInput">
                    <div class="my-5">
                        <flux:input
                            type="text"
                            name="recovery_code"
                            x-model="recovery_code"
                            placeholder="XXXX-XXXX-XXXX-XXXX"
                            autocomplete="one-time-code"
                        />
                    </div>

                    @error('recovery_code')
                        <flux:text class="text-red-400 text-sm">
                            {{ $message }}
                        </flux:text>
                    @enderror
                </div>

                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full"
                >
                    {{ __('Continue') }}
                </flux:button>
            </div>

            <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                <span class="text-zinc-400">{{ __('or you can') }}</span>
                <div class="inline font-medium underline cursor-pointer text-indigo-400 hover:text-indigo-300">
                    <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                    <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                </div>
            </div>
        </form>

        <div class="text-center">
            <a href="{{ route('superadmin.login') }}" class="text-sm text-zinc-400 hover:text-zinc-300">
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</x-layouts.superadmin.auth>
