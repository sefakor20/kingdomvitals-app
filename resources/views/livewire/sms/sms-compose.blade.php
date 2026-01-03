<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Compose SMS') }}</flux:heading>
            <flux:subheading>{{ __('Send SMS to members of :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('sms.index', $branch)" icon="arrow-left" wire:navigate>
            {{ __('Back to SMS') }}
        </flux:button>
    </div>

    <!-- Not Configured Warning -->
    @if(!$this->isSmsConfigured)
        <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/30">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:heading size="sm" class="text-yellow-900 dark:text-yellow-100">{{ __('SMS Not Configured') }}</flux:heading>
                    <flux:text class="text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('Please configure your TextTango API key and Sender ID in branch settings to send SMS messages.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    @else
        <!-- Account Balance Card -->
        @if($this->accountBalance['success'] ?? false)
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                            <flux:icon icon="currency-dollar" class="size-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Account Balance') }}</flux:text>
                            <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                                {{ $this->accountBalance['currency'] ?? 'GHS' }} {{ number_format($this->accountBalance['balance'] ?? 0, 2) }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <!-- Recipient Type Tabs -->
                <div>
                    <flux:text class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Send To') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        <flux:button
                            variant="{{ $recipientType === 'individual' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="$set('recipientType', 'individual')"
                            icon="user"
                        >
                            {{ __('Individual Members') }}
                        </flux:button>
                        <flux:button
                            variant="{{ $recipientType === 'cluster' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="$set('recipientType', 'cluster')"
                            icon="user-group"
                        >
                            {{ __('Cluster') }}
                        </flux:button>
                        <flux:button
                            variant="{{ $recipientType === 'all_members' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="$set('recipientType', 'all_members')"
                            icon="users"
                        >
                            {{ __('All Members') }}
                        </flux:button>
                    </div>
                </div>

                <!-- Recipient Selection -->
                @if($recipientType === 'individual')
                    <div>
                        <flux:text class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Select Members') }}</flux:text>
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @forelse($this->members as $member)
                                <label
                                    class="flex cursor-pointer items-center gap-3 border-b border-zinc-100 px-4 py-3 transition last:border-b-0 dark:border-zinc-800 {{ $member->sms_opt_out ? 'bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:hover:bg-yellow-900/30' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                                    wire:key="member-{{ $member->id }}"
                                >
                                    <flux:checkbox
                                        wire:model.live="selectedMemberIds"
                                        value="{{ $member->id }}"
                                    />
                                    <div class="flex flex-1 items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            @if($member->photo_url)
                                                <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-8 rounded-full object-cover" />
                                            @else
                                                <flux:avatar size="sm" name="{{ $member->fullName() }}" />
                                            @endif
                                            <div>
                                                <flux:text class="text-sm text-zinc-900 dark:text-zinc-100">{{ $member->fullName() }}</flux:text>
                                                <flux:text class="text-xs text-zinc-500">{{ $member->phone }}</flux:text>
                                            </div>
                                        </div>
                                        @if($member->sms_opt_out)
                                            <flux:badge size="sm" color="yellow">{{ __('Opted Out') }}</flux:badge>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="p-4 text-center text-sm text-zinc-500">
                                    {{ __('No members with phone numbers found.') }}
                                </div>
                            @endforelse
                        </div>
                        @if(count($selectedMemberIds) > 0)
                            <flux:text class="mt-2 text-sm text-zinc-500">
                                {{ count($selectedMemberIds) }} {{ __('member(s) selected') }}
                            </flux:text>
                        @endif
                    </div>
                @elseif($recipientType === 'cluster')
                    <div>
                        <flux:select wire:model.live="selectedClusterId" :label="__('Select Cluster')">
                            <flux:select.option value="">{{ __('Choose a cluster...') }}</flux:select.option>
                            @foreach($this->clusters as $cluster)
                                <flux:select.option value="{{ $cluster->id }}">
                                    {{ $cluster->name }} ({{ $cluster->members_count }} {{ __('members with phone') }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @else
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="users" class="size-5 text-green-600 dark:text-green-400" />
                            <flux:text class="text-green-700 dark:text-green-300">
                                {{ __('Message will be sent to all :count members with phone numbers.', ['count' => $this->members->count()]) }}
                            </flux:text>
                        </div>
                    </div>
                @endif

                @error('recipients')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <!-- Template Selection -->
                <div>
                    <flux:select wire:model.live="templateId" :label="__('Use Template (Optional)')">
                        <flux:select.option value="">{{ __('No template - Write custom message') }}</flux:select.option>
                        @foreach($this->templates as $template)
                            <flux:select.option value="{{ $template->id }}">
                                {{ $template->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Message Type -->
                <div>
                    <flux:select wire:model="messageType" :label="__('Message Type')">
                        @foreach($this->smsTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Message Composition -->
                <div>
                    <flux:textarea
                        wire:model.live="message"
                        :label="__('Message')"
                        rows="6"
                        placeholder="{{ __('Enter your message here...') }}"
                    />
                    <div class="mt-2 flex items-center justify-between text-sm text-zinc-500">
                        <div>
                            {{ $this->characterCount }} {{ __('characters') }}
                            @if($this->characterCount > 160)
                                <span class="text-yellow-600 dark:text-yellow-400">
                                    ({{ $this->smsPartCount }} {{ __('SMS parts') }})
                                </span>
                            @endif
                        </div>
                        <div>
                            @if($this->characterCount > 1600)
                                <span class="text-red-500">{{ __('Message too long') }}</span>
                            @elseif($this->smsPartCount > 1)
                                <span class="text-yellow-600 dark:text-yellow-400">
                                    {{ __('Will be sent as :count parts', ['count' => $this->smsPartCount]) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @error('message')
                        <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                    @enderror

                    <!-- Placeholder Help -->
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Available Placeholders (click to insert):') }}
                        </flux:text>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($this->availablePlaceholders as $placeholder => $description)
                                <button
                                    type="button"
                                    x-on:click="
                                        const textarea = $el.closest('.space-y-6').querySelector('textarea');
                                        const start = textarea.selectionStart;
                                        const end = textarea.selectionEnd;
                                        const text = textarea.value;
                                        textarea.value = text.substring(0, start) + '{{ $placeholder }}' + text.substring(end);
                                        textarea.selectionStart = textarea.selectionEnd = start + '{{ $placeholder }}'.length;
                                        textarea.focus();
                                        $wire.set('message', textarea.value);
                                    "
                                    class="inline-flex items-center rounded-md bg-zinc-200 px-2 py-1 text-xs font-mono text-zinc-700 transition hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                                    title="{{ $description }}"
                                >
                                    {{ $placeholder }}
                                </button>
                            @endforeach
                        </div>
                        <flux:text class="mt-2 text-xs text-zinc-500">
                            {{ __('These placeholders will be replaced with actual member details when the message is sent.') }}
                        </flux:text>
                    </div>
                </div>

                <!-- Schedule Option -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="isScheduled" />
                        <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Schedule for later') }}</flux:text>
                    </div>

                    @if($isScheduled)
                        <div>
                            <flux:input
                                wire:model="scheduledAt"
                                type="datetime-local"
                                :label="__('Send At')"
                                min="{{ now()->format('Y-m-d\TH:i') }}"
                            />
                            @error('scheduledAt')
                                <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="resetForm">
                        {{ __('Reset') }}
                    </flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="preview"
                        icon="eye"
                        :disabled="!$this->isSmsConfigured"
                    >
                        {{ __('Preview & Send') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Sidebar Summary -->
        <div class="lg:col-span-1">
            <div class="sticky top-4 space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Summary') }}</flux:heading>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-zinc-500">{{ __('Recipients') }}</flux:text>
                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->recipientCount) }}
                        </flux:text>
                    </div>

                    <div class="flex items-center justify-between">
                        <flux:text class="text-zinc-500">{{ __('Characters') }}</flux:text>
                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->characterCount) }}
                        </flux:text>
                    </div>

                    <div class="flex items-center justify-between">
                        <flux:text class="text-zinc-500">{{ __('SMS Parts') }}</flux:text>
                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->smsPartCount ?: 1 }}
                        </flux:text>
                    </div>

                    @if($isScheduled && $scheduledAt)
                        <div class="flex items-center justify-between">
                            <flux:text class="text-zinc-500">{{ __('Scheduled') }}</flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ \Carbon\Carbon::parse($scheduledAt)->format('M d, H:i') }}
                            </flux:text>
                        </div>
                    @endif

                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium text-zinc-500">{{ __('Total Messages') }}</flux:text>
                            <flux:text class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($this->recipientCount * max($this->smsPartCount, 1)) }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                @if($this->recipientCount === 0)
                    <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                        <flux:text class="text-sm text-zinc-500">
                            {{ __('Select recipients to continue.') }}
                        </flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <flux:modal wire:model.self="showPreviewModal" name="preview" class="w-full max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Preview SMS') }}</flux:heading>

            <!-- Message Preview -->
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-500">{{ __('Message') }}</flux:text>
                <div class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:text class="whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $message }}</flux:text>
                </div>
                <flux:text class="mt-1 text-xs text-zinc-500">
                    {{ $this->characterCount }} {{ __('characters') }} ({{ $this->smsPartCount }} {{ __('SMS part(s)') }})
                </flux:text>
            </div>

            <!-- Recipients List -->
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-500">
                    {{ __('Recipients') }} ({{ count($previewRecipients) }})
                    @if($optedOutCount > 0)
                        <span class="text-yellow-600 dark:text-yellow-400">
                            ({{ $optedOutCount }} {{ __('opted out') }})
                        </span>
                    @endif
                </flux:text>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach($previewRecipients as $recipient)
                        <div
                            class="flex items-center justify-between gap-2 border-b border-zinc-100 px-4 py-2 last:border-b-0 dark:border-zinc-800 {{ ($recipient['sms_opt_out'] ?? false) ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}"
                            wire:key="preview-{{ $recipient['id'] }}"
                        >
                            <div class="flex items-center gap-2">
                                <flux:icon icon="user" class="size-4 text-zinc-400" />
                                <flux:text class="text-sm text-zinc-900 dark:text-zinc-100">{{ $recipient['name'] }}</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ $recipient['phone'] }}</flux:text>
                            </div>
                            @if($recipient['sms_opt_out'] ?? false)
                                <flux:badge size="sm" color="yellow">{{ __('Opted Out') }}</flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($isScheduled && $scheduledAt)
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/30">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        <flux:icon icon="clock" class="inline size-4" />
                        {{ __('Scheduled for :date', ['date' => \Carbon\Carbon::parse($scheduledAt)->format('M d, Y H:i')]) }}
                    </flux:text>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="closePreview">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="confirmSend" icon="paper-airplane">
                    {{ __('Send Now') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Confirmation Modal -->
    <flux:modal wire:model.self="showConfirmModal" name="confirm" class="w-full max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="exclamation-triangle" class="size-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <flux:heading size="lg">{{ __('Confirm Send') }}</flux:heading>
            </div>

            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ __('You are about to send :count SMS message(s). This action cannot be undone and will use your SMS credits.', ['count' => count($previewRecipients)]) }}
            </flux:text>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="cancelConfirm">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="send" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="send">{{ __('Confirm & Send') }}</span>
                    <span wire:loading wire:target="send">{{ __('Sending...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Modal -->
    <flux:modal wire:model.self="showSuccessModal" name="success" class="w-full max-w-md">
        <div class="space-y-4 text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <flux:icon icon="check-circle" class="size-10 text-green-600 dark:text-green-400" />
            </div>

            <flux:heading size="lg">{{ __('SMS Queued Successfully!') }}</flux:heading>

            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ __(':count SMS message(s) have been queued for delivery.', ['count' => $sentCount]) }}
            </flux:text>

            <div class="pt-4">
                <flux:button variant="primary" wire:click="closeSuccess">
                    {{ __('View SMS History') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Opted-Out Warning Modal -->
    <flux:modal wire:model.self="showOptedOutWarningModal" name="opted-out-warning" class="w-full max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="exclamation-triangle" class="size-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <flux:heading size="lg">{{ __('Some Recipients Have Opted Out') }}</flux:heading>
            </div>

            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ trans_choice(
                    '{1} :count member has opted out of receiving SMS messages. They will still receive this message since you are sending it manually.|[2,*] :count members have opted out of receiving SMS messages. They will still receive this message since you are sending it manually.',
                    $optedOutCount,
                    ['count' => $optedOutCount]
                ) }}
            </flux:text>

            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/30">
                <flux:text class="text-sm text-yellow-700 dark:text-yellow-300">
                    <strong>{{ __('Note:') }}</strong> {{ __('Opted-out members are automatically excluded from automated messages (birthday wishes, service reminders, etc.) but can still receive manual SMS.') }}
                </flux:text>
            </div>

            <!-- List opted-out recipients -->
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-500">{{ __('Opted-out recipients:') }}</flux:text>
                <div class="max-h-32 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach(collect($previewRecipients)->where('sms_opt_out', true) as $recipient)
                        <div
                            class="flex items-center gap-2 border-b border-zinc-100 bg-yellow-50 px-4 py-2 last:border-b-0 dark:border-zinc-800 dark:bg-yellow-900/20"
                            wire:key="opted-out-{{ $recipient['id'] }}"
                        >
                            <flux:icon icon="user" class="size-4 text-yellow-600 dark:text-yellow-400" />
                            <flux:text class="text-sm text-zinc-900 dark:text-zinc-100">{{ $recipient['name'] }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ $recipient['phone'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="cancelOptedOutWarning">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="acknowledgeOptedOutWarning">
                    {{ __('Continue Anyway') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
