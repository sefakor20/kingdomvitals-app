<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Branch Settings') }}</flux:heading>
            <flux:subheading>{{ __('Configure settings for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('sms.index', $branch)" icon="arrow-left" wire:navigate>
            {{ __('Back to SMS') }}
        </flux:button>
    </div>

    <!-- SMS Configuration Section -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="chat-bubble-left-right" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('SMS Configuration') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Configure your TextTango API credentials for sending SMS messages.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-6">
            <!-- API Key -->
            <div>
                <flux:input
                    wire:model="smsApiKey"
                    type="{{ $showApiKey ? 'text' : 'password' }}"
                    :label="__('API Key')"
                    placeholder="{{ $hasExistingApiKey ? __('Enter new API key to replace existing') : __('Enter your TextTango API key') }}"
                />
                @if($hasExistingApiKey)
                    <div class="mt-2 flex items-center gap-2">
                        <flux:badge color="green" size="sm">{{ __('Configured') }}</flux:badge>
                        <flux:button variant="ghost" size="sm" wire:click="clearApiKey" class="text-red-600 hover:text-red-700">
                            {{ __('Clear') }}
                        </flux:button>
                    </div>
                @endif
                @error('smsApiKey')
                    <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                @enderror
            </div>

            <!-- Sender ID -->
            <div>
                <flux:input
                    wire:model="smsSenderId"
                    :label="__('Sender ID')"
                    placeholder="{{ __('e.g., MyChurch') }}"
                    maxlength="11"
                />
                <flux:text class="mt-1 text-xs text-zinc-500">
                    {{ __('Maximum 11 alphanumeric characters. This will appear as the sender name on recipient phones.') }}
                </flux:text>
                @error('smsSenderId')
                    <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                @enderror
            </div>

            <!-- Test Connection Result -->
            @if($testConnectionResult)
                <div class="rounded-lg border p-4 {{ $testConnectionStatus === 'success' ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/30' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/30' }}">
                    <div class="flex items-center gap-2">
                        <flux:icon
                            icon="{{ $testConnectionStatus === 'success' ? 'check-circle' : 'x-circle' }}"
                            class="size-5 {{ $testConnectionStatus === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                        />
                        <flux:text class="{{ $testConnectionStatus === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ $testConnectionResult }}
                        </flux:text>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex items-center justify-between border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:button variant="ghost" wire:click="testConnection" icon="signal" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="testConnection">{{ __('Test Connection') }}</span>
                    <span wire:loading wire:target="testConnection">{{ __('Testing...') }}</span>
                </flux:button>

                <flux:button variant="primary" wire:click="save" icon="check" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Save Settings') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Automated SMS Section -->
    <div class="mt-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="clock" class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Automated SMS') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Configure automated SMS messages for birthdays and special occasions.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-6">
            @if(!$hasExistingApiKey)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                        <flux:text class="text-amber-700 dark:text-amber-300">
                            {{ __('Please configure your SMS credentials above before enabling automated SMS.') }}
                        </flux:text>
                    </div>
                </div>
            @else
                <!-- Birthday SMS Toggle -->
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Birthday Greetings') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Automatically send birthday wishes to members on their special day.') }}
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="autoBirthdaySms" />
                </div>

                @if($autoBirthdaySms)
                    <!-- Birthday Template Selection -->
                    <div>
                        <flux:select
                            wire:model="birthdayTemplateId"
                            :label="__('Birthday Message Template')"
                        >
                            <flux:select.option value="">{{ __('Use default message') }}</flux:select.option>
                            @foreach($this->birthdayTemplates as $template)
                                <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            {{ __('Select a template or use the default: "Happy Birthday, {first_name}! Wishing you a blessed and wonderful day filled with joy."') }}
                        </flux:text>
                        @if($this->birthdayTemplates->isEmpty())
                            <div class="mt-2">
                                <flux:button variant="ghost" size="sm" :href="route('sms.templates', $branch)" wire:navigate icon="plus">
                                    {{ __('Manage Templates') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700"></div>

                <!-- Welcome SMS Toggle -->
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('New Member Welcome') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Automatically send a welcome message when new members are added.') }}
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="autoWelcomeSms" />
                </div>

                @if($autoWelcomeSms)
                    <!-- Welcome Template Selection -->
                    <div>
                        <flux:select
                            wire:model="welcomeTemplateId"
                            :label="__('Welcome Message Template')"
                        >
                            <flux:select.option value="">{{ __('Use default message') }}</flux:select.option>
                            @foreach($this->welcomeTemplates as $template)
                                <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            {{ __('Available placeholders: {first_name}, {last_name}, {full_name}, {branch_name}') }}
                        </flux:text>
                        @if($this->welcomeTemplates->isEmpty())
                            <div class="mt-2">
                                <flux:button variant="ghost" size="sm" :href="route('sms.templates', $branch)" wire:navigate icon="plus">
                                    {{ __('Manage Templates') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700"></div>

                <!-- Service Reminder SMS Toggle -->
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Service Reminders') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Automatically remind members before upcoming services.') }}
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="autoServiceReminder" />
                </div>

                @if($autoServiceReminder)
                    <!-- Service Reminder Settings -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <flux:select
                                wire:model="serviceReminderHours"
                                :label="__('Send Reminder')"
                            >
                                <flux:select.option value="6">{{ __('6 hours before') }}</flux:select.option>
                                <flux:select.option value="12">{{ __('12 hours before') }}</flux:select.option>
                                <flux:select.option value="24">{{ __('24 hours before (Recommended)') }}</flux:select.option>
                                <flux:select.option value="48">{{ __('48 hours before') }}</flux:select.option>
                            </flux:select>
                        </div>

                        <div>
                            <flux:select
                                wire:model="serviceReminderRecipients"
                                :label="__('Send To')"
                            >
                                <flux:select.option value="all">{{ __('All Members') }}</flux:select.option>
                                <flux:select.option value="attendees">{{ __('Regular Attendees Only') }}</flux:select.option>
                            </flux:select>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ __('Regular attendees are members who have attended this service before.') }}
                            </flux:text>
                        </div>
                    </div>

                    <div>
                        <flux:select
                            wire:model="serviceReminderTemplateId"
                            :label="__('Reminder Message Template')"
                        >
                            <flux:select.option value="">{{ __('Use default message') }}</flux:select.option>
                            @foreach($this->reminderTemplates as $template)
                                <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            {{ __('Available placeholders: {first_name}, {service_name}, {service_time}, {service_day}, {branch_name}') }}
                        </flux:text>
                        @if($this->reminderTemplates->isEmpty())
                            <div class="mt-2">
                                <flux:button variant="ghost" size="sm" :href="route('sms.templates', $branch)" wire:navigate icon="plus">
                                    {{ __('Manage Templates') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            <!-- Save Button for Auto SMS -->
            <div class="flex justify-end border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:button variant="primary" wire:click="save" icon="check" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Save Settings') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-3">{{ __('Need Help?') }}</flux:heading>
        <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <p>{{ __('To get your TextTango API credentials:') }}</p>
            <ol class="ml-4 list-decimal space-y-1">
                <li>{{ __('Log in to your') }} <a href="https://app.texttango.com/" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ __('TextTango') }}</a> {{ __('account') }}</li>
                <li>{{ __('Navigate to Developer > Access Tokens') }}</li>
                <li>{{ __('Create a new API key or copy your existing one') }}</li>
                <li>{{ __('For Sender ID, use your organization name (max 11 characters)') }}</li>
            </ol>
        </div>
    </div>

    <!-- Success Toasts -->
    <x-toast on="settings-saved" type="success">
        {{ __('SMS settings saved successfully.') }}
    </x-toast>

    <x-toast on="api-key-cleared" type="success">
        {{ __('API key cleared.') }}
    </x-toast>
</section>
