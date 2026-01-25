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

    <!-- Tabs -->
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4 overflow-x-auto">
            <button
                wire:click="setActiveTab('sms')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'sms' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="chat-bubble-left-right" class="size-4" />
                {{ __('SMS Credentials') }}
            </button>
            <button
                wire:click="setActiveTab('automation')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'automation' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="clock" class="size-4" />
                {{ __('Automated Messages') }}
            </button>
            <button
                wire:click="setActiveTab('payment')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'payment' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="credit-card" class="size-4" />
                {{ __('Payment Gateway') }}
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    @if($activeTab === 'sms')
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

                <!-- Help Section -->
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="mb-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Where to find your API key:') }}</p>
                    <ol class="ml-4 list-decimal space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <li>{{ __('Log in to your') }} <a href="https://app.texttango.com/" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ __('TextTango account') }}</a></li>
                        <li>{{ __('Navigate to Developer > Access Tokens') }}</li>
                        <li>{{ __('Create a new API key or copy your existing one') }}</li>
                    </ol>
                </div>

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
    @endif

    @if($activeTab === 'automation')
        <!-- Automated SMS Section -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
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
                                {{ __('Please configure your SMS credentials in the SMS Credentials tab before enabling automated SMS.') }}
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

                    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700"></div>

                    <!-- Attendance Follow-up SMS Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">{{ __('Attendance Follow-up') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Automatically send follow-up messages to members who missed a service.') }}
                            </flux:text>
                        </div>
                        <flux:switch wire:model.live="autoAttendanceFollowup" />
                    </div>

                    @if($autoAttendanceFollowup)
                        <!-- Attendance Follow-up Settings -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:select
                                    wire:model="attendanceFollowupHours"
                                    :label="__('Send Follow-up')"
                                >
                                    <flux:select.option value="6">{{ __('6 hours after service') }}</flux:select.option>
                                    <flux:select.option value="12">{{ __('12 hours after service') }}</flux:select.option>
                                    <flux:select.option value="24">{{ __('24 hours after service (Recommended)') }}</flux:select.option>
                                    <flux:select.option value="48">{{ __('48 hours after service') }}</flux:select.option>
                                </flux:select>
                            </div>

                            <div>
                                <flux:select
                                    wire:model.live="attendanceFollowupRecipients"
                                    :label="__('Send To')"
                                >
                                    <flux:select.option value="regular">{{ __('Regular Attendees Only') }}</flux:select.option>
                                    <flux:select.option value="all">{{ __('All Active Members') }}</flux:select.option>
                                </flux:select>
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    {{ __('Regular attendees are members who have attended this service multiple times.') }}
                                </flux:text>
                            </div>
                        </div>

                        @if($attendanceFollowupRecipients === 'regular')
                            <div>
                                <flux:select
                                    wire:model="attendanceFollowupMinAttendance"
                                    :label="__('Minimum Past Attendances')"
                                >
                                    <flux:select.option value="2">{{ __('2 times in last 2 months') }}</flux:select.option>
                                    <flux:select.option value="3">{{ __('3 times in last 2 months (Recommended)') }}</flux:select.option>
                                    <flux:select.option value="4">{{ __('4 times in last 2 months') }}</flux:select.option>
                                    <flux:select.option value="5">{{ __('5 times in last 2 months') }}</flux:select.option>
                                </flux:select>
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    {{ __('Only members who attended at least this many times will receive follow-ups.') }}
                                </flux:text>
                            </div>
                        @endif

                        <div>
                            <flux:select
                                wire:model="attendanceFollowupTemplateId"
                                :label="__('Follow-up Message Template')"
                            >
                                <flux:select.option value="">{{ __('Use default message') }}</flux:select.option>
                                @foreach($this->followupTemplates as $template)
                                    <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ __('Available placeholders: {first_name}, {service_name}, {service_day}, {branch_name}') }}
                            </flux:text>
                            @if($this->followupTemplates->isEmpty())
                                <div class="mt-2">
                                    <flux:button variant="ghost" size="sm" :href="route('sms.templates', $branch)" wire:navigate icon="plus">
                                        {{ __('Manage Templates') }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700"></div>

                    <!-- Duty Roster Reminder Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">{{ __('Duty Roster Reminders') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Automatically remind preachers, liturgists, and readers before their assigned service.') }}
                            </flux:text>
                        </div>
                        <flux:switch wire:model.live="autoDutyRosterReminder" />
                    </div>

                    @if($autoDutyRosterReminder)
                        <!-- Duty Roster Reminder Settings -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:select
                                    wire:model="dutyRosterReminderDays"
                                    :label="__('Send Reminder')"
                                >
                                    <flux:select.option value="1">{{ __('1 day before') }}</flux:select.option>
                                    <flux:select.option value="2">{{ __('2 days before') }}</flux:select.option>
                                    <flux:select.option value="3">{{ __('3 days before (Recommended)') }}</flux:select.option>
                                    <flux:select.option value="5">{{ __('5 days before') }}</flux:select.option>
                                    <flux:select.option value="7">{{ __('7 days before') }}</flux:select.option>
                                </flux:select>
                            </div>

                            <div>
                                <flux:field>
                                    <flux:label>{{ __('Notification Channels') }}</flux:label>
                                    <div class="mt-2 space-y-2">
                                        <flux:checkbox
                                            wire:model="dutyRosterReminderChannels"
                                            value="sms"
                                            label="{{ __('SMS') }}"
                                        />
                                        <flux:checkbox
                                            wire:model="dutyRosterReminderChannels"
                                            value="email"
                                            label="{{ __('Email') }}"
                                        />
                                    </div>
                                </flux:field>
                            </div>
                        </div>

                        <div>
                            <flux:select
                                wire:model="dutyRosterReminderTemplateId"
                                :label="__('SMS Message Template')"
                            >
                                <flux:select.option value="">{{ __('Use default message') }}</flux:select.option>
                                @foreach($this->dutyRosterReminderTemplates as $template)
                                    <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ __('Available placeholders: {first_name}, {last_name}, {full_name}, {role}, {service_date}, {theme}, {branch_name}') }}
                            </flux:text>
                            @if($this->dutyRosterReminderTemplates->isEmpty())
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
    @endif

    @if($activeTab === 'payment')
        <!-- Paystack Configuration Section -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="credit-card" class="size-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Online Giving (Paystack)') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Configure Paystack credentials to accept online donations.') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="space-y-6 p-6">
                <!-- Public Key -->
                <div>
                    <flux:input
                        wire:model="paystackPublicKey"
                        type="text"
                        :label="__('Public Key')"
                        placeholder="{{ $hasExistingPaystackKeys ? __('Enter new public key to replace existing') : __('pk_test_... or pk_live_...') }}"
                    />
                    <flux:text class="mt-1 text-xs text-zinc-500">
                        {{ __('Starts with pk_test_ (test mode) or pk_live_ (live mode)') }}
                    </flux:text>
                </div>

                <!-- Secret Key -->
                <div>
                    <flux:input
                        wire:model="paystackSecretKey"
                        type="{{ $showPaystackSecretKey ? 'text' : 'password' }}"
                        :label="__('Secret Key')"
                        placeholder="{{ $hasExistingPaystackKeys ? __('Enter new secret key to replace existing') : __('sk_test_... or sk_live_...') }}"
                    />
                    <flux:text class="mt-1 text-xs text-zinc-500">
                        {{ __('Starts with sk_test_ (test mode) or sk_live_ (live mode). Keep this secret!') }}
                    </flux:text>
                </div>

                @if($hasExistingPaystackKeys)
                    <div class="flex items-center gap-2">
                        <flux:badge color="green" size="sm">{{ __('Configured') }}</flux:badge>
                        <flux:button variant="ghost" size="sm" wire:click="clearPaystackKeys" class="text-red-600 hover:text-red-700">
                            {{ __('Clear Keys') }}
                        </flux:button>
                    </div>
                @endif

                <!-- Test Mode Toggle -->
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <flux:heading size="sm">{{ __('Test Mode') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Enable test mode to process test payments without real money.') }}
                        </flux:text>
                    </div>
                    <flux:switch wire:model="paystackTestMode" />
                </div>

                <!-- Giving URL -->
                @if($hasExistingPaystackKeys)
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30">
                        <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">{{ __('Your Giving Page') }}</flux:heading>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 rounded bg-white px-3 py-2 text-sm dark:bg-zinc-800">{{ $this->givingUrl }}</code>
                            <flux:button variant="ghost" size="sm" icon="clipboard" onclick="navigator.clipboard.writeText('{{ $this->givingUrl }}')">
                                {{ __('Copy') }}
                            </flux:button>
                        </div>
                        <flux:text class="mt-2 text-xs text-green-600 dark:text-green-400">
                            {{ __('Share this link with your congregation to receive online donations.') }}
                        </flux:text>
                    </div>
                @endif

                <!-- Test Connection Result -->
                @if($paystackTestConnectionResult)
                    <div class="rounded-lg border p-4 {{ $paystackTestConnectionStatus === 'success' ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/30' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/30' }}">
                        <div class="flex items-center gap-2">
                            <flux:icon
                                icon="{{ $paystackTestConnectionStatus === 'success' ? 'check-circle' : 'x-circle' }}"
                                class="size-5 {{ $paystackTestConnectionStatus === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                            />
                            <flux:text class="{{ $paystackTestConnectionStatus === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                {{ $paystackTestConnectionResult }}
                            </flux:text>
                        </div>
                    </div>
                @endif

                <!-- Help Section -->
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="mb-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Where to find your API keys:') }}</p>
                    <ol class="ml-4 list-decimal space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <li>{{ __('Log in to your') }} <a href="https://dashboard.paystack.com/" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ __('Paystack Dashboard') }}</a></li>
                        <li>{{ __('Go to Settings > API Keys & Webhooks') }}</li>
                        <li>{{ __('Copy your Public Key and Secret Key') }}</li>
                        <li>{{ __('Use Test keys for testing, Live keys for real payments') }}</li>
                    </ol>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="testPaystackConnection" icon="signal" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="testPaystackConnection">{{ __('Test Connection') }}</span>
                        <span wire:loading wire:target="testPaystackConnection">{{ __('Testing...') }}</span>
                    </flux:button>

                    <flux:button variant="primary" wire:click="savePaystackSettings" icon="check" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="savePaystackSettings">{{ __('Save Paystack Settings') }}</span>
                        <span wire:loading wire:target="savePaystackSettings">{{ __('Saving...') }}</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Toasts -->
    <x-toast on="settings-saved" type="success">
        {{ __('SMS settings saved successfully.') }}
    </x-toast>

    <x-toast on="api-key-cleared" type="success">
        {{ __('API key cleared.') }}
    </x-toast>

    <x-toast on="paystack-settings-saved" type="success">
        {{ __('Paystack settings saved successfully.') }}
    </x-toast>

    <x-toast on="paystack-keys-cleared" type="success">
        {{ __('Paystack keys cleared.') }}
    </x-toast>
</section>
