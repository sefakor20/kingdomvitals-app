<div class="w-full">
    <div class="mb-8">
        <flux:heading size="xl" level="1">{{ __('Subscription') }}</flux:heading>
        <flux:subheading>
            {{ __('View your current plan and usage limits') }}
        </flux:subheading>
    </div>

    {{-- Cancellation Status Banner --}}
    @if($this->tenant->isCancelled())
        <div class="mb-8 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div>
                        <flux:heading size="sm" class="text-amber-800 dark:text-amber-200">
                            {{ __('Subscription Cancelled') }}
                        </flux:heading>
                        <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                            {{ __('Your subscription has been cancelled. You have access until :date.', ['date' => $this->tenant->subscription_ends_at->format('M d, Y')]) }}
                        </flux:text>
                        @if($this->tenant->subscriptionDaysRemaining() !== null)
                            <flux:text class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                {{ trans_choice(':count day remaining|:count days remaining', $this->tenant->subscriptionDaysRemaining(), ['count' => $this->tenant->subscriptionDaysRemaining()]) }}
                            </flux:text>
                        @endif
                    </div>
                </div>
                <flux:button wire:click="reactivateSubscription" variant="primary" size="sm">
                    {{ __('Reactivate Subscription') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="space-y-8">
        {{-- Current Plan Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Current Plan') }}</flux:heading>

            @if($this->plan)
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="xl">{{ $this->plan->name }}</flux:heading>
                            @if($this->plan->description)
                                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                                    {{ $this->plan->description }}
                                </flux:text>
                            @endif
                        </div>
                        @if($this->supportLevel)
                            <flux:badge color="zinc" size="sm">
                                {{ ucfirst($this->supportLevel) }} {{ __('Support') }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                    <div class="flex items-center gap-3">
                        <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                        <div>
                            <flux:heading size="sm">{{ __('No Active Plan') }}</flux:heading>
                            <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                                {{ __('You currently have no subscription plan. All features are available.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Usage Quotas Section --}}
        @if($this->hasAnyQuotaLimits)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Usage Quotas') }}</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- Members Quota --}}
                    @unless($this->memberQuota['unlimited'])
                        @php
                            $memberColor = match(true) {
                                $this->memberQuota['percent'] >= 100 => 'red',
                                $this->memberQuota['percent'] >= 80 => 'amber',
                                default => 'blue',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Members') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->memberQuota['current'] }} / {{ $this->memberQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $memberColor }}-500 transition-all"
                                     style="width: {{ min($this->memberQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->memberQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Branches Quota --}}
                    @unless($this->branchQuota['unlimited'])
                        @php
                            $branchColor = match(true) {
                                $this->branchQuota['percent'] >= 100 => 'red',
                                $this->branchQuota['percent'] >= 80 => 'amber',
                                default => 'cyan',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Branches') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->branchQuota['current'] }} / {{ $this->branchQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $branchColor }}-500 transition-all"
                                     style="width: {{ min($this->branchQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->branchQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- SMS Credits Quota --}}
                    @unless($this->smsQuota['unlimited'])
                        @php
                            $smsColor = match(true) {
                                $this->smsQuota['percent'] >= 100 => 'red',
                                $this->smsQuota['percent'] >= 80 => 'amber',
                                default => 'green',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('SMS Credits') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->smsQuota['sent'] }} / {{ $this->smsQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $smsColor }}-500 transition-all"
                                     style="width: {{ min($this->smsQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->smsQuota['remaining'] }} {{ __('remaining this month') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Storage Quota --}}
                    @unless($this->storageQuota['unlimited'])
                        @php
                            $storageColor = match(true) {
                                $this->storageQuota['percent'] >= 100 => 'red',
                                $this->storageQuota['percent'] >= 80 => 'amber',
                                default => 'purple',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Storage') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($this->storageQuota['used'], 2) }} / {{ $this->storageQuota['max'] }} GB
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $storageColor }}-500 transition-all"
                                     style="width: {{ min($this->storageQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ number_format($this->storageQuota['remaining'], 2) }} GB {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Households Quota --}}
                    @unless($this->householdQuota['unlimited'])
                        @php
                            $householdColor = match(true) {
                                $this->householdQuota['percent'] >= 100 => 'red',
                                $this->householdQuota['percent'] >= 80 => 'amber',
                                default => 'indigo',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Households') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->householdQuota['current'] }} / {{ $this->householdQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $householdColor }}-500 transition-all"
                                     style="width: {{ min($this->householdQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->householdQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Clusters Quota --}}
                    @unless($this->clusterQuota['unlimited'])
                        @php
                            $clusterColor = match(true) {
                                $this->clusterQuota['percent'] >= 100 => 'red',
                                $this->clusterQuota['percent'] >= 80 => 'amber',
                                default => 'teal',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Clusters') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->clusterQuota['current'] }} / {{ $this->clusterQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $clusterColor }}-500 transition-all"
                                     style="width: {{ min($this->clusterQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->clusterQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Visitors Quota --}}
                    @unless($this->visitorQuota['unlimited'])
                        @php
                            $visitorColor = match(true) {
                                $this->visitorQuota['percent'] >= 100 => 'red',
                                $this->visitorQuota['percent'] >= 80 => 'amber',
                                default => 'orange',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Visitors') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->visitorQuota['current'] }} / {{ $this->visitorQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $visitorColor }}-500 transition-all"
                                     style="width: {{ min($this->visitorQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->visitorQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless

                    {{-- Equipment Quota --}}
                    @unless($this->equipmentQuota['unlimited'])
                        @php
                            $equipmentColor = match(true) {
                                $this->equipmentQuota['percent'] >= 100 => 'red',
                                $this->equipmentQuota['percent'] >= 80 => 'amber',
                                default => 'rose',
                            };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-medium">{{ __('Equipment') }}</flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->equipmentQuota['current'] }} / {{ $this->equipmentQuota['max'] }}
                                </flux:text>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-{{ $equipmentColor }}-500 transition-all"
                                     style="width: {{ min($this->equipmentQuota['percent'], 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ $this->equipmentQuota['remaining'] }} {{ __('remaining') }}
                            </flux:text>
                        </div>
                    @endunless
                </div>
            </div>
        @else
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Usage Quotas') }}</flux:heading>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                    <div class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                        <flux:text class="text-green-700 dark:text-green-300">
                            {{ __('Your plan has unlimited usage for all resources.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        {{-- Enabled Modules Section --}}
        @if($this->plan && count($this->enabledModules) > 0)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Enabled Modules') }}</flux:heading>
                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                    @foreach($this->enabledModules as $module)
                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <flux:icon name="check" class="size-4 text-green-500" />
                            <flux:text class="text-sm">{{ ucfirst(str_replace('_', ' ', $module)) }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$this->plan)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Enabled Modules') }}</flux:heading>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                    <div class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                        <flux:text class="text-green-700 dark:text-green-300">
                            {{ __('All modules are enabled.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        {{-- Features Section --}}
        @if($this->plan && count($this->features) > 0)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Features') }}</flux:heading>
                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                    @foreach($this->features as $feature)
                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <flux:icon name="check" class="size-4 text-green-500" />
                            <flux:text class="text-sm">{{ ucfirst(str_replace('_', ' ', $feature)) }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$this->plan)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Features') }}</flux:heading>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                    <div class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                        <flux:text class="text-green-700 dark:text-green-300">
                            {{ __('All features are enabled.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        {{-- Upgrade Section --}}
        @if($this->plan && !$this->tenant->isCancelled())
            <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="font-medium">{{ __('Need more?') }}</flux:text>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Upgrade your plan to unlock additional features and higher limits.') }}
                        </flux:text>
                    </div>
                    @if(Route::has('plans.index'))
                        <flux:button variant="primary" href="{{ route('plans.index') }}" wire:navigate>
                            {{ __('Upgrade Plan') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Danger Zone - Cancel Subscription --}}
        @if($this->plan && !$this->tenant->isCancelled())
            <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <flux:heading size="sm" class="text-red-800 dark:text-red-200">
                        {{ __('Danger Zone') }}
                    </flux:heading>
                    <flux:text class="mt-2 text-sm text-red-700 dark:text-red-300">
                        {{ __('Once you cancel your subscription, you will lose access to premium features at the end of your billing period. Your data will be preserved.') }}
                    </flux:text>
                    <div class="mt-4">
                        <flux:button wire:click="confirmCancellation" variant="danger" size="sm">
                            {{ __('Cancel Subscription') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Cancellation Confirmation Modal --}}
    @if($this->plan && !$this->tenant->isCancelled())
        <flux:modal wire:model="showCancelModal" class="max-w-md">
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Cancel Subscription') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                        {{ __('Are you sure you want to cancel your subscription? You will retain access until the end of your current billing period.') }}
                    </flux:text>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        <strong>{{ __('What happens when you cancel:') }}</strong>
                    </flux:text>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-amber-600 dark:text-amber-400">
                        <li>{{ __('Access continues until :date', ['date' => now()->endOfMonth()->format('M d, Y')]) }}</li>
                        <li>{{ __('Your data will be preserved') }}</li>
                        <li>{{ __('You can reactivate anytime before the end date') }}</li>
                    </ul>
                </div>

                <flux:field>
                    <flux:label>{{ __('Please tell us why you\'re cancelling') }}</flux:label>
                    <flux:textarea
                        wire:model="cancellationReason"
                        rows="3"
                        placeholder="{{ __('Your feedback helps us improve...') }}"
                    />
                    <flux:error name="cancellationReason" />
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:button wire:click="$set('showCancelModal', false)" variant="ghost">
                        {{ __('Keep Subscription') }}
                    </flux:button>
                    <flux:button wire:click="cancelSubscription" variant="danger">
                        {{ __('Yes, Cancel Subscription') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
