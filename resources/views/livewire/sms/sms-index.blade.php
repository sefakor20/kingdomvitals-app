<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('SMS Messages') }}</flux:heading>
            <flux:subheading>{{ __('View SMS history for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('sms.analytics', $branch)" icon="chart-bar" wire:navigate>
                {{ __('Analytics') }}
            </flux:button>
            @if($this->smsRecords->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @can('create', [\App\Models\Tenant\SmsLog::class, $branch])
                <flux:button variant="primary" :href="route('sms.compose', $branch)" icon="paper-airplane" wire:navigate>
                    {{ __('Compose SMS') }}
                </flux:button>
            @endcan
        </div>
    </div>

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

    {{-- SMS Quota Warning Banner --}}
    @if($this->showQuotaWarning && !$this->smsQuota['unlimited'])
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Approaching SMS Limit') }}
                    </flux:text>
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('You have sent :sent of :max SMS this month (:percent% used).', [
                            'sent' => $this->smsQuota['sent'],
                            'max' => $this->smsQuota['max'],
                            'percent' => $this->smsQuota['percent'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'sms']) }}" variant="ghost" size="sm">
                    {{ __('Upgrade') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- SMS Quota Exceeded Banner --}}
    @if(!$this->smsQuota['unlimited'] && ($this->smsQuota['remaining'] ?? 0) <= 0)
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-red-800 dark:text-red-200">
                        {{ __('SMS Limit Reached') }}
                    </flux:text>
                    <flux:text class="text-sm text-red-700 dark:text-red-300">
                        {{ __('You have used all :max SMS credits for this month. Upgrade your plan to send more.', [
                            'max' => $this->smsQuota['max'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'sms']) }}" variant="primary" size="sm">
                    {{ __('Upgrade Now') }}
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Sent') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="paper-airplane" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->smsStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Delivered') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->smsStats['delivered']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pending') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="clock" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->smsStats['pending']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Failed') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="x-circle" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->smsStats['failed']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Cost') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="banknotes" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->smsStats['currency'] }} {{ number_format($this->smsStats['cost'], 2) }}</flux:heading>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="mb-4 flex flex-wrap gap-2">
        <flux:button
            variant="{{ $quickFilter === 'today' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('today')"
        >
            {{ __('Today') }}
        </flux:button>
        <flux:button
            variant="{{ $quickFilter === 'this_week' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('this_week')"
        >
            {{ __('This Week') }}
        </flux:button>
        <flux:button
            variant="{{ $quickFilter === 'this_month' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('this_month')"
        >
            {{ __('This Month') }}
        </flux:button>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by phone or member name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->smsStatuses as $status)
                    <flux:select.option value="{{ $status->value }}">
                        {{ ucfirst($status->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->smsTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ ucfirst($type->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live="dateFrom" type="date" :label="__('From Date')" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live="dateTo" type="date" :label="__('To Date')" />
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    @if($this->smsRecords->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="chat-bubble-left-right" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No SMS messages found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('SMS messages will appear here when you send them.') }}
                @endif
            </flux:text>
            @can('create', [\App\Models\Tenant\SmsLog::class, $branch])
                <flux:button variant="primary" :href="route('sms.compose', $branch)" icon="paper-airplane" class="mt-4" wire:navigate>
                    {{ __('Send Your First SMS') }}
                </flux:button>
            @endcan
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Recipient') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Message') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Cost') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->smsRecords as $sms)
                        <tr wire:key="sms-{{ $sms->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $sms->created_at?->format('M d, Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($sms->member)
                                        @if($sms->member->photo_url)
                                            <img src="{{ $sms->member->photo_url }}" alt="{{ $sms->member->fullName() }}" class="size-8 rounded-full object-cover" />
                                        @else
                                            <flux:avatar size="sm" name="{{ $sms->member->fullName() }}" />
                                        @endif
                                        <div>
                                            <a
                                                href="{{ route('members.show', [$branch, $sms->member]) }}"
                                                class="text-sm text-zinc-900 hover:text-blue-600 hover:underline dark:text-zinc-100 dark:hover:text-blue-400"
                                                wire:navigate
                                            >
                                                {{ $sms->member->fullName() }}
                                            </a>
                                            <div class="text-xs text-zinc-500">{{ $sms->phone_number }}</div>
                                        </div>
                                    @else
                                        <flux:avatar size="sm" name="?" />
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $sms->phone_number }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="max-w-xs truncate px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ Str::limit($sms->message, 50) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($sms->message_type?->value) {
                                        'birthday' => 'pink',
                                        'reminder' => 'yellow',
                                        'announcement' => 'blue',
                                        'follow_up' => 'purple',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($sms->message_type?->value ?? 'custom') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($sms->status?->value) {
                                        'delivered' => 'green',
                                        'sent' => 'blue',
                                        'pending' => 'yellow',
                                        'failed' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($sms->status?->value ?? 'pending') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $sms->currency }} {{ number_format((float) $sms->cost, 4) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewMessage('{{ $sms->id }}')" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        </div>

        @if($this->smsRecords->hasPages())
            <div class="mt-4">
                {{ $this->smsRecords->links() }}
            </div>
        @endif
    @endif

    <!-- View Message Modal -->
    <flux:modal wire:model.self="showMessageModal" name="view-message" class="w-full max-w-lg">
        @if($viewingMessage)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('SMS Details') }}</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Recipient') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">
                            {{ $viewingMessage->member?->fullName() ?? $viewingMessage->phone_number }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Phone Number') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $viewingMessage->phone_number }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Message') }}</flux:text>
                        <div class="mt-1 rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                            <flux:text class="whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $viewingMessage->message }}</flux:text>
                        </div>
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            {{ strlen($viewingMessage->message) }} {{ __('characters') }} ({{ ceil(strlen($viewingMessage->message) / 160) }} {{ __('SMS part(s)') }})
                        </flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Type') }}</flux:text>
                            <flux:badge
                                :color="match($viewingMessage->message_type?->value) {
                                    'birthday' => 'pink',
                                    'reminder' => 'yellow',
                                    'announcement' => 'blue',
                                    'follow_up' => 'purple',
                                    default => 'zinc',
                                }"
                                size="sm"
                                class="mt-1"
                            >
                                {{ ucfirst($viewingMessage->message_type?->value ?? 'custom') }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Status') }}</flux:text>
                            <flux:badge
                                :color="match($viewingMessage->status?->value) {
                                    'delivered' => 'green',
                                    'sent' => 'blue',
                                    'pending' => 'yellow',
                                    'failed' => 'red',
                                    default => 'zinc',
                                }"
                                size="sm"
                                class="mt-1"
                            >
                                {{ ucfirst($viewingMessage->status?->value ?? 'pending') }}
                            </flux:badge>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Sent At') }}</flux:text>
                            <flux:text class="text-zinc-900 dark:text-zinc-100">
                                {{ $viewingMessage->sent_at?->format('M d, Y H:i') ?? '-' }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Delivered At') }}</flux:text>
                            <flux:text class="text-zinc-900 dark:text-zinc-100">
                                {{ $viewingMessage->delivered_at?->format('M d, Y H:i') ?? '-' }}
                            </flux:text>
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Cost') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">
                            {{ $viewingMessage->currency }} {{ number_format((float) $viewingMessage->cost, 4) }}
                        </flux:text>
                    </div>

                    @if($viewingMessage->error_message)
                        <div>
                            <flux:text class="text-sm font-medium text-red-500">{{ __('Error') }}</flux:text>
                            <flux:text class="text-red-600 dark:text-red-400">{{ $viewingMessage->error_message }}</flux:text>
                        </div>
                    @endif

                    @if($viewingMessage->provider_message_id)
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Provider ID') }}</flux:text>
                            <flux:text class="font-mono text-xs text-zinc-500">{{ $viewingMessage->provider_message_id }}</flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button variant="ghost" wire:click="closeMessageModal">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
