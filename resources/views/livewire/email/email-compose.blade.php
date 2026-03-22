<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Compose Email') }}</flux:heading>
            <flux:subheading>{{ __('Send bulk emails to members') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('email.templates', $branch)" icon="document-text" wire:navigate>
                {{ __('Templates') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('email.index', $branch)" icon="arrow-left" wire:navigate>
                {{ __('Back to Emails') }}
            </flux:button>
        </div>
    </div>

    {{-- Email Quota Warning --}}
    @if($this->showQuotaWarning && !$this->emailQuota['unlimited'])
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Approaching Email Limit') }}
                    </flux:text>
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('You have sent :sent of :max emails this month (:percent% used).', [
                            'sent' => $this->emailQuota['sent'],
                            'max' => $this->emailQuota['max'],
                            'percent' => $this->emailQuota['percent'],
                        ]) }}
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left Column - Recipients -->
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Recipients') }}</flux:heading>

                <!-- Recipient Type Selection -->
                <div class="mb-4">
                    <flux:select wire:model.live="recipientType" :label="__('Send To')">
                        <flux:select.option value="individual">{{ __('Select Individual Members') }}</flux:select.option>
                        <flux:select.option value="cluster">{{ __('Select a Cluster/Group') }}</flux:select.option>
                        <flux:select.option value="all_members">{{ __('All Members with Email') }}</flux:select.option>
                    </flux:select>
                </div>

                @if($recipientType === 'individual')
                    <div>
                        <flux:text class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Select Members') }}</flux:text>
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @forelse($this->members as $member)
                                <label
                                    class="flex cursor-pointer items-center gap-3 border-b border-zinc-100 px-4 py-3 transition last:border-b-0 dark:border-zinc-800 {{ $member->email_opt_out ? 'bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:hover:bg-yellow-900/30' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
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
                                                <flux:text class="text-xs text-zinc-500">{{ $member->email }}</flux:text>
                                            </div>
                                        </div>
                                        @if($member->email_opt_out)
                                            <flux:badge size="sm" color="yellow">{{ __('Opted Out') }}</flux:badge>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="p-4 text-center text-sm text-zinc-500">
                                    {{ __('No members with email addresses found.') }}
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
                    <flux:select wire:model.live="selectedClusterId">
                        <flux:select.option value="">{{ __('Select a cluster...') }}</flux:select.option>
                        @foreach($this->clusters as $cluster)
                            <flux:select.option value="{{ $cluster->id }}">
                                {{ $cluster->name }} ({{ $cluster->members_count }} {{ __('members') }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('All :count members with email addresses will receive this email.', ['count' => $this->members->count()]) }}
                    </flux:text>
                @endif

                <div class="mt-4 rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium">{{ __('Recipients') }}</flux:text>
                        <flux:badge color="blue">{{ $this->recipientCount }}</flux:badge>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Message -->
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Message') }}</flux:heading>

                <!-- Template Selection -->
                <div class="mb-4">
                    <flux:select wire:model.live="templateId" :label="__('Load Template')">
                        <flux:select.option value="">{{ __('Start from scratch...') }}</flux:select.option>
                        @foreach($this->templates as $template)
                            <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Email Type -->
                <div class="mb-4">
                    <flux:select wire:model="messageType" :label="__('Email Type')">
                        @foreach($this->emailTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Subject -->
                <div class="mb-4">
                    <flux:input
                        wire:model="subject"
                        :label="__('Subject')"
                        placeholder="{{ __('Enter email subject...') }}"
                    />
                    @error('subject')
                        <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                    @enderror
                </div>

                <!-- Body with Markdown Editor -->
                <div class="mb-4" x-data="{ activeTab: 'edit' }">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Body') }}</flux:text>
                        <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <button
                                type="button"
                                @click="activeTab = 'edit'"
                                :class="activeTab === 'edit' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-l-lg transition"
                            >
                                {{ __('Edit') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'preview'"
                                :class="activeTab === 'preview' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-r-lg transition"
                            >
                                {{ __('Preview') }}
                            </button>
                        </div>
                    </div>

                    <!-- Edit Tab -->
                    <div x-show="activeTab === 'edit'">
                        <flux:textarea
                            wire:model.live.debounce.300ms="body"
                            placeholder="{{ __('Write your email using Markdown...') }}"
                            rows="10"
                            class="font-mono text-sm"
                        />
                        @error('body')
                            <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <!-- Preview Tab -->
                    <div x-show="activeTab === 'preview'">
                        <div class="min-h-[250px] rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            @if($body)
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! $this->bodyPreview !!}
                                </div>
                            @else
                                <flux:text class="text-zinc-400 italic">{{ __('Nothing to preview yet. Start writing in the Edit tab.') }}</flux:text>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Markdown Formatting Guide -->
                <div class="mb-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Markdown Formatting') }}</flux:text>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                        @foreach($this->markdownGuide as $syntax => $description)
                            <span><code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">{{ $syntax }}</code> → {{ $description }}</span>
                        @endforeach
                    </div>
                </div>

                <!-- Placeholders -->
                <div class="mb-6 rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:text class="mb-2 text-sm font-medium">{{ __('Available Placeholders') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->availablePlaceholders as $placeholder => $description)
                            <flux:badge
                                size="sm"
                                class="cursor-help"
                                title="{{ $description }}"
                            >
                                {{ $placeholder }}
                            </flux:badge>
                        @endforeach
                    </div>
                </div>

                <!-- Errors -->
                @error('recipients')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    </div>
                @enderror

                @error('quota')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    </div>
                @enderror

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="resetForm">
                        {{ __('Reset') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="preview" :disabled="!$this->canSendWithinQuota || $this->recipientCount === 0">
                        {{ __('Preview & Send') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <flux:modal wire:model.self="showPreviewModal" name="preview-email" class="w-full max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Preview Email') }}</flux:heading>

            <div class="space-y-3">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ __('Subject') }}</flux:text>
                    <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $subject }}</flux:text>
                </div>

                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ __('Recipients') }} ({{ count($previewRecipients) }})</flux:text>
                    <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach($previewRecipients as $recipient)
                            <div class="flex items-center gap-2 py-1">
                                <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $recipient['name'] }}</span>
                                <span class="text-xs text-zinc-500">({{ $recipient['email'] }})</span>
                                @if($recipient['email_opt_out'] ?? false)
                                    <flux:badge color="red" size="sm">{{ __('Opted Out') }}</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ __('Message Preview') }}</flux:text>
                    <div class="mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! $this->bodyPreview !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="closePreview">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="confirmSend">
                    {{ __('Continue') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Confirm Modal -->
    <flux:modal wire:model.self="showConfirmModal" name="confirm-send" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Confirm Send') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to send this email to :count recipients?', ['count' => count($previewRecipients)]) }}
            </flux:text>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="cancelConfirm">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="send">
                    {{ __('Send Email') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Modal -->
    <flux:modal wire:model.self="showSuccessModal" name="success" class="w-full max-w-md">
        <div class="space-y-4 text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <flux:icon icon="check" class="size-8 text-green-600 dark:text-green-400" />
            </div>

            <flux:heading size="lg">{{ __('Emails Queued!') }}</flux:heading>

            <flux:text>
                {{ __(':count emails have been queued for delivery.', ['count' => $sentCount]) }}
            </flux:text>

            <flux:button variant="primary" wire:click="closeSuccess" class="w-full">
                {{ __('Done') }}
            </flux:button>
        </div>
    </flux:modal>

    <!-- Opted-Out Warning Modal -->
    <flux:modal wire:model.self="showOptedOutWarningModal" name="opted-out-warning" class="w-full max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                    <flux:icon icon="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <flux:heading size="lg">{{ __('Opted-Out Recipients') }}</flux:heading>
            </div>

            <flux:text>
                {{ __(':count of your selected recipients have opted out of emails. They will still receive this email unless you remove them.', ['count' => $optedOutCount]) }}
            </flux:text>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="cancelOptedOutWarning">
                    {{ __('Go Back') }}
                </flux:button>
                <flux:button variant="primary" wire:click="acknowledgeOptedOutWarning">
                    {{ __('Continue Anyway') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
