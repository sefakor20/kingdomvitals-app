<div>
    <!-- Header -->
    <div class="mb-8">
        <flux:heading size="xl">{{ __('System Settings') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            {{ __('Configure platform-wide settings, integration defaults, and feature flags.') }}
        </flux:text>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4 overflow-x-auto">
            <button
                wire:click="setActiveTab('application')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'application' ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
            >
                <flux:icon.cog-6-tooth class="size-4" />
                {{ __('Application') }}
            </button>
            <button
                wire:click="setActiveTab('integrations')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'integrations' ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
            >
                <flux:icon.puzzle-piece class="size-4" />
                {{ __('Integration Defaults') }}
            </button>
            <button
                wire:click="setActiveTab('features')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'features' ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
            >
                <flux:icon.adjustments-horizontal class="size-4" />
                {{ __('Feature Flags') }}
            </button>
        </nav>
    </div>

    <!-- Application Tab -->
    @if($activeTab === 'application')
        <!-- Platform Logo Section -->
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                        <flux:icon.photo class="size-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Platform Logo') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ __('Upload a logo to be used across the platform. Tenants can override this with their own logo.') }}</flux:text>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <!-- Current Logo Preview -->
                    <div class="shrink-0">
                        <div class="relative h-32 w-32 overflow-hidden rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700">
                            @if($platformLogo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                <img
                                    src="{{ $platformLogo->temporaryUrl() }}"
                                    alt="{{ __('New logo preview') }}"
                                    class="h-full w-full object-contain p-2"
                                />
                            @elseif($existingPlatformLogoUrl)
                                <img
                                    src="{{ $existingPlatformLogoUrl }}"
                                    alt="{{ __('Current platform logo') }}"
                                    class="h-full w-full object-contain p-2"
                                />
                            @else
                                <div class="flex h-full w-full flex-col items-center justify-center text-zinc-400">
                                    <flux:icon.photo class="size-8" />
                                    <span class="mt-1 text-xs">{{ __('No logo') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Upload Controls -->
                    <div class="flex-1 space-y-4">
                        @if($canModify)
                            <div>
                                <flux:field>
                                    <flux:label>{{ __('Upload New Logo') }}</flux:label>
                                    <input
                                        type="file"
                                        wire:model="platformLogo"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-zinc-400 dark:file:bg-indigo-900/50 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900"
                                    />
                                    <flux:description>{{ __('PNG, JPG, or WebP. Minimum 256x256 pixels. Maximum 2MB.') }}</flux:description>
                                    <flux:error name="platformLogo" />
                                </flux:field>
                            </div>

                            <div class="flex gap-2">
                                @if($platformLogo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    <flux:button wire:click="savePlatformLogo" variant="primary" size="sm">
                                        {{ __('Save Logo') }}
                                    </flux:button>
                                    <flux:button wire:click="$set('platformLogo', null)" variant="ghost" size="sm">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                @endif

                                @if($existingPlatformLogoUrl && !$platformLogo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    <flux:button wire:click="removePlatformLogo" variant="ghost" size="sm" class="text-red-600 hover:text-red-700">
                                        {{ __('Remove Logo') }}
                                    </flux:button>
                                @endif
                            </div>
                        @else
                            <flux:callout variant="warning" icon="lock-closed">
                                <flux:callout.text>{{ __('Only owners can modify the platform logo.') }}</flux:callout.text>
                            </flux:callout>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Application Settings') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Configure general application settings.') }}</flux:text>
            </div>
            <form wire:submit="saveApplicationSettings" class="space-y-6 p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- App Name -->
                    <flux:field>
                        <flux:label>{{ __('Application Name') }}</flux:label>
                        <flux:input
                            wire:model="appName"
                            type="text"
                            placeholder="Kingdom Vitals"
                            :disabled="!$canModify"
                        />
                        <flux:error name="appName" />
                    </flux:field>

                    <!-- Support Email -->
                    <flux:field>
                        <flux:label>{{ __('Support Email') }}</flux:label>
                        <flux:input
                            wire:model="supportEmail"
                            type="email"
                            placeholder="support@example.com"
                            :disabled="!$canModify"
                        />
                        <flux:error name="supportEmail" />
                    </flux:field>

                    <!-- Default Trial Days -->
                    <flux:field>
                        <flux:label>{{ __('Default Trial Days') }}</flux:label>
                        <flux:input
                            wire:model="defaultTrialDays"
                            type="number"
                            min="0"
                            max="365"
                            :disabled="!$canModify"
                        />
                        <flux:description>{{ __('Number of trial days for new tenants.') }}</flux:description>
                        <flux:error name="defaultTrialDays" />
                    </flux:field>

                    <!-- Currency -->
                    <flux:field>
                        <flux:label>{{ __('Currency') }}</flux:label>
                        <flux:select wire:model="currency" :disabled="!$canModify">
                            <option value="GHS">GHS (Ghana Cedis)</option>
                            <option value="USD">USD (US Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="GBP">GBP (British Pound)</option>
                            <option value="NGN">NGN (Nigerian Naira)</option>
                        </flux:select>
                        <flux:error name="currency" />
                    </flux:field>

                    <!-- Date Format -->
                    <flux:field>
                        <flux:label>{{ __('Date Format') }}</flux:label>
                        <flux:select wire:model="dateFormat" :disabled="!$canModify">
                            <option value="Y-m-d">{{ now()->format('Y-m-d') }} (Y-m-d)</option>
                            <option value="d/m/Y">{{ now()->format('d/m/Y') }} (d/m/Y)</option>
                            <option value="m/d/Y">{{ now()->format('m/d/Y') }} (m/d/Y)</option>
                            <option value="d M Y">{{ now()->format('d M Y') }} (d M Y)</option>
                            <option value="M d, Y">{{ now()->format('M d, Y') }} (M d, Y)</option>
                        </flux:select>
                        <flux:error name="dateFormat" />
                    </flux:field>
                </div>

                <!-- Maintenance Mode -->
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <flux:switch
                                    wire:model.live="maintenanceMode"
                                    :disabled="!$canModify"
                                />
                                <div>
                                    <flux:heading size="sm">{{ __('Maintenance Mode') }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('When enabled, tenants will see a maintenance message.') }}
                                    </flux:text>
                                </div>
                            </div>
                            @if($maintenanceMode)
                                <div class="mt-4">
                                    <flux:field>
                                        <flux:label>{{ __('Maintenance Message') }}</flux:label>
                                        <flux:textarea
                                            wire:model="maintenanceMessage"
                                            rows="2"
                                            placeholder="{{ __('We are performing scheduled maintenance. Please check back soon.') }}"
                                            :disabled="!$canModify"
                                        />
                                        <flux:error name="maintenanceMessage" />
                                    </flux:field>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($canModify)
                    <div class="flex justify-end border-t border-zinc-200 pt-6 dark:border-zinc-700">
                        <flux:button type="submit" variant="primary">
                            {{ __('Save Application Settings') }}
                        </flux:button>
                    </div>
                @else
                    <flux:callout variant="warning" icon="lock-closed">
                        <flux:callout.heading>{{ __('Read-Only Access') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Only owners can modify system settings.') }}</flux:callout.text>
                    </flux:callout>
                @endif
            </form>
        </div>
    @endif

    <!-- Integrations Tab -->
    @if($activeTab === 'integrations')
        <div class="space-y-6">
            <!-- Paystack Defaults -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                            <flux:icon.credit-card class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:heading size="lg">{{ __('Default Paystack Credentials') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Default payment gateway credentials for new tenants.') }}</flux:text>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Public Key') }}</flux:label>
                            <flux:input
                                wire:model="defaultPaystackPublicKey"
                                type="text"
                                placeholder="pk_test_..."
                                :disabled="!$canModify"
                            />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Secret Key') }}</flux:label>
                            <flux:input
                                wire:model="defaultPaystackSecretKey"
                                type="password"
                                placeholder="sk_test_..."
                                :disabled="!$canModify"
                            />
                        </flux:field>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="defaultPaystackTestMode" :disabled="!$canModify" />
                        <flux:text>{{ __('Test Mode') }}</flux:text>
                    </div>

                    @if($paystackTestResult)
                        <flux:callout :variant="$paystackTestStatus === 'success' ? 'success' : 'danger'" :icon="$paystackTestStatus === 'success' ? 'check-circle' : 'x-circle'">
                            {{ $paystackTestResult }}
                        </flux:callout>
                    @endif

                    @if($canModify)
                        <div class="flex gap-2">
                            <flux:button wire:click="testPaystackConnection" variant="ghost">
                                {{ __('Test Connection') }}
                            </flux:button>
                            @if($hasExistingPaystackKeys)
                                <flux:button wire:click="clearPaystackKeys" variant="ghost" class="text-red-600 hover:text-red-700">
                                    {{ __('Clear Keys') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- SMS Defaults -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <flux:icon.chat-bubble-left class="size-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:heading size="lg">{{ __('Default SMS Credentials') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Default SMS provider credentials for new tenants.') }}</flux:text>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('API Key') }}</flux:label>
                            <flux:input
                                wire:model="defaultSmsApiKey"
                                type="password"
                                placeholder="{{ __('Enter API key') }}"
                                :disabled="!$canModify"
                            />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Sender ID') }}</flux:label>
                            <flux:input
                                wire:model="defaultSmsSenderId"
                                type="text"
                                placeholder="KingdomV"
                                maxlength="11"
                                :disabled="!$canModify"
                            />
                            <flux:description>{{ __('Max 11 characters, letters and numbers only.') }}</flux:description>
                        </flux:field>
                    </div>

                    @if($smsTestResult)
                        <flux:callout :variant="$smsTestStatus === 'success' ? 'success' : 'danger'" :icon="$smsTestStatus === 'success' ? 'check-circle' : 'x-circle'">
                            {{ $smsTestResult }}
                        </flux:callout>
                    @endif

                    @if($canModify)
                        <div class="flex gap-2">
                            <flux:button wire:click="testSmsConnection" variant="ghost">
                                {{ __('Test Connection') }}
                            </flux:button>
                            @if($hasExistingSmsKey)
                                <flux:button wire:click="clearSmsKey" variant="ghost" class="text-red-600 hover:text-red-700">
                                    {{ __('Clear API Key') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Webhook URL -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Webhook Configuration') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ __('Base URL for webhook endpoints.') }}</flux:text>
                </div>
                <div class="p-6">
                    <flux:field>
                        <flux:label>{{ __('Webhook Base URL') }}</flux:label>
                        <flux:input
                            wire:model="webhookBaseUrl"
                            type="url"
                            placeholder="https://api.example.com"
                            :disabled="!$canModify"
                        />
                        <flux:description>{{ __('The base URL used for generating webhook callback URLs.') }}</flux:description>
                        <flux:error name="webhookBaseUrl" />
                    </flux:field>
                </div>
            </div>

            @if($canModify)
                <div class="flex justify-end">
                    <flux:button wire:click="saveIntegrationSettings" variant="primary">
                        {{ __('Save Integration Settings') }}
                    </flux:button>
                </div>
            @else
                <flux:callout variant="warning" icon="lock-closed">
                    <flux:callout.heading>{{ __('Read-Only Access') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Only owners can modify system settings.') }}</flux:callout.text>
                </flux:callout>
            @endif
        </div>
    @endif

    <!-- Features Tab -->
    @if($activeTab === 'features')
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Feature Flags') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Enable or disable features across the entire platform.') }}</flux:text>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <!-- Donations -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                            <flux:icon.banknotes class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Donations Module') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Allow tenants to accept online donations.') }}</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="donationsEnabled" :disabled="!$canModify" />
                </div>

                <!-- SMS -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <flux:icon.chat-bubble-left-right class="size-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('SMS Messaging') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Allow tenants to send SMS messages to members.') }}</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="smsEnabled" :disabled="!$canModify" />
                </div>

                <!-- Member Portal -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                            <flux:icon.user-group class="size-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Member Portal') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Allow members to access a self-service portal.') }}</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="memberPortalEnabled" :disabled="!$canModify" />
                </div>

                <!-- Tenant 2FA -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                            <flux:icon.shield-check class="size-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Two-Factor Authentication') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Allow tenant users to enable 2FA for their accounts.') }}</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="tenant2faEnabled" :disabled="!$canModify" />
                </div>

                <!-- API Access -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                            <flux:icon.code-bracket class="size-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('API Access') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ __('Allow tenants to access the API for integrations.') }}</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="tenantApiAccessEnabled" :disabled="!$canModify" />
                </div>
            </div>

            @if($canModify)
                <div class="flex justify-end border-t border-zinc-200 p-6 dark:border-zinc-700">
                    <flux:button wire:click="saveFeatureSettings" variant="primary">
                        {{ __('Save Feature Settings') }}
                    </flux:button>
                </div>
            @else
                <div class="p-6">
                    <flux:callout variant="warning" icon="lock-closed">
                        <flux:callout.heading>{{ __('Read-Only Access') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Only owners can modify system settings.') }}</flux:callout.text>
                    </flux:callout>
                </div>
            @endif
        </div>
    @endif

    <!-- Toast Notifications -->
    <x-toast on="settings-saved" type="success">
        {{ __('Settings saved successfully.') }}
    </x-toast>
    <x-toast on="credentials-cleared" type="success">
        {{ __('Credentials cleared successfully.') }}
    </x-toast>
    <x-toast on="logo-saved" type="success">
        {{ __('Logo uploaded successfully.') }}
    </x-toast>
    <x-toast on="logo-removed" type="success">
        {{ __('Logo removed successfully.') }}
    </x-toast>
</div>
