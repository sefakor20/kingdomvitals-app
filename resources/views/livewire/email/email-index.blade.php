<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Email Messages') }}</flux:heading>
            <flux:subheading>{{ __('View email history for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('email.analytics', $branch)" icon="chart-bar" wire:navigate>
                {{ __('Analytics') }}
            </flux:button>
            @if($this->emailRecords->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @can('create', [\App\Models\Tenant\EmailLog::class, $branch])
                <flux:button variant="primary" :href="route('email.compose', $branch)" icon="paper-airplane" wire:navigate>
                    {{ __('Compose Email') }}
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Email Quota Warning Banner --}}
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
                <flux:button href="{{ route('upgrade.required', ['module' => 'email']) }}" variant="ghost" size="sm">
                    {{ __('Upgrade') }}
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
            <flux:heading size="xl" class="mt-2">{{ number_format($this->emailStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Delivered') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->emailStats['delivered']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Opened') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="envelope-open" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->emailStats['opened']) }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ $this->emailStats['open_rate'] }}% {{ __('rate') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Clicked') }}</flux:text>
                <div class="rounded-full bg-indigo-100 p-2 dark:bg-indigo-900">
                    <flux:icon icon="cursor-arrow-rays" class="size-4 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->emailStats['clicked']) }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ $this->emailStats['click_rate'] }}% {{ __('rate') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Failed/Bounced') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="x-circle" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->emailStats['failed'] + $this->emailStats['bounced']) }}</flux:heading>
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
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by email, subject or member name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->emailStatuses as $status)
                    <flux:select.option value="{{ $status->value }}">
                        {{ ucfirst($status->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->emailTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ ucfirst(str_replace('_', ' ', $type->value)) }}
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

    @if($this->emailRecords->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="envelope" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No email messages found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Email messages will appear here when you send them.') }}
                @endif
            </flux:text>
            @can('create', [\App\Models\Tenant\EmailLog::class, $branch])
                <flux:button variant="primary" :href="route('email.compose', $branch)" icon="paper-airplane" class="mt-4" wire:navigate>
                    {{ __('Send Your First Email') }}
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
                            {{ __('Subject') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Engagement') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->emailRecords as $email)
                        <tr wire:key="email-{{ $email->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $email->created_at?->format('M d, Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($email->member)
                                        @if($email->member->photo_url)
                                            <img src="{{ $email->member->photo_url }}" alt="{{ $email->member->fullName() }}" class="size-8 rounded-full object-cover" />
                                        @else
                                            <flux:avatar size="sm" name="{{ $email->member->fullName() }}" />
                                        @endif
                                        <div>
                                            <a
                                                href="{{ route('members.show', [$branch, $email->member]) }}"
                                                class="text-sm text-zinc-900 hover:text-blue-600 hover:underline dark:text-zinc-100 dark:hover:text-blue-400"
                                                wire:navigate
                                            >
                                                {{ $email->member->fullName() }}
                                            </a>
                                            <div class="text-xs text-zinc-500">{{ $email->email_address }}</div>
                                        </div>
                                    @else
                                        <flux:avatar size="sm" name="?" />
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $email->email_address }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="max-w-xs truncate px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ Str::limit($email->subject, 40) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($email->message_type?->value) {
                                        'birthday' => 'pink',
                                        'reminder' => 'yellow',
                                        'announcement' => 'blue',
                                        'newsletter' => 'purple',
                                        'follow_up' => 'indigo',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $email->message_type?->value ?? 'custom')) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($email->status?->value) {
                                        'delivered' => 'green',
                                        'sent' => 'blue',
                                        'pending' => 'yellow',
                                        'bounced' => 'orange',
                                        'failed' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($email->status?->value ?? 'pending') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($email->opened_at)
                                        <flux:icon icon="envelope-open" class="size-4 text-green-500" title="{{ __('Opened') }}" />
                                    @endif
                                    @if($email->clicked_at)
                                        <flux:icon icon="cursor-arrow-rays" class="size-4 text-blue-500" title="{{ __('Clicked') }}" />
                                    @endif
                                    @if(!$email->opened_at && !$email->clicked_at)
                                        <span class="text-xs text-zinc-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewMessage('{{ $email->id }}')" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        </div>

        @if($this->emailRecords->hasPages())
            <div class="mt-4">
                {{ $this->emailRecords->links() }}
            </div>
        @endif
    @endif

    <!-- View Message Modal -->
    <flux:modal wire:model.self="showMessageModal" name="view-message" class="w-full max-w-2xl">
        @if($viewingMessage)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Email Details') }}</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Subject') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $viewingMessage->subject }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Recipient') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">
                            {{ $viewingMessage->member?->fullName() ?? $viewingMessage->email_address }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Email Address') }}</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $viewingMessage->email_address }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">{{ __('Body') }}</flux:text>
                        <div class="mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            {!! $viewingMessage->body !!}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Type') }}</flux:text>
                            <flux:badge
                                :color="match($viewingMessage->message_type?->value) {
                                    'birthday' => 'pink',
                                    'reminder' => 'yellow',
                                    'announcement' => 'blue',
                                    'newsletter' => 'purple',
                                    default => 'zinc',
                                }"
                                size="sm"
                                class="mt-1"
                            >
                                {{ ucfirst(str_replace('_', ' ', $viewingMessage->message_type?->value ?? 'custom')) }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Status') }}</flux:text>
                            <flux:badge
                                :color="match($viewingMessage->status?->value) {
                                    'delivered' => 'green',
                                    'sent' => 'blue',
                                    'pending' => 'yellow',
                                    'bounced' => 'orange',
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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Opened At') }}</flux:text>
                            <flux:text class="text-zinc-900 dark:text-zinc-100">
                                {{ $viewingMessage->opened_at?->format('M d, Y H:i') ?? '-' }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Clicked At') }}</flux:text>
                            <flux:text class="text-zinc-900 dark:text-zinc-100">
                                {{ $viewingMessage->clicked_at?->format('M d, Y H:i') ?? '-' }}
                            </flux:text>
                        </div>
                    </div>

                    @if($viewingMessage->error_message)
                        <div>
                            <flux:text class="text-sm font-medium text-red-500">{{ __('Error') }}</flux:text>
                            <flux:text class="text-red-600 dark:text-red-400">{{ $viewingMessage->error_message }}</flux:text>
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
