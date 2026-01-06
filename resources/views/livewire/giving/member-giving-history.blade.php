<section class="w-full">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('My Giving History') }}</flux:heading>
            <flux:subheading>{{ __('View your donation history and download receipts') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('giving.form', $branch)" icon="heart" wire:navigate>
            {{ __('Give Now') }}
        </flux:button>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Year Selector & Total --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-2 flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total in :year', ['year' => $year]) }}</flux:text>
                <flux:select wire:model.live="year" class="w-24" size="sm">
                    @foreach($this->availableYears as $y)
                        <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                GHS {{ number_format((float) $this->givingStats['thisYear'], 2) }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->givingStats['yearCount'] }} {{ __('donations') }}
            </flux:text>
        </div>

        {{-- Lifetime Total --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lifetime Total') }}</flux:text>
            <flux:heading size="xl">
                GHS {{ number_format((float) $this->givingStats['total'], 2) }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->givingStats['count'] }} {{ __('donations') }}
            </flux:text>
        </div>

        {{-- Recurring Status --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
            <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Recurring Giving') }}</flux:text>
            @if($this->recurringDonations->isNotEmpty())
                <div class="flex items-center gap-2">
                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                    <flux:text class="text-sm">
                        {{ $this->recurringDonations->count() }} {{ __('active subscription(s)') }}
                    </flux:text>
                </div>
            @else
                <flux:text class="text-zinc-600 dark:text-zinc-400">{{ __('No recurring donations set up') }}</flux:text>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:select wire:model.live="typeFilter" label="{{ __('Donation Type') }}">
                    <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                    @foreach($this->donationTypes as $type)
                        <flux:select.option value="{{ $type->value }}">
                            {{ ucwords(str_replace('_', ' ', $type->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex-1">
                <flux:input wire:model.live="dateFrom" type="date" label="{{ __('From Date') }}" />
            </div>

            <div class="flex-1">
                <flux:input wire:model.live="dateTo" type="date" label="{{ __('To Date') }}" />
            </div>

            @if($this->hasActiveFilters)
                <flux:button variant="ghost" wire:click="clearFilters" size="sm">
                    {{ __('Clear Filters') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Donations List --}}
    @if($this->donations->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <flux:icon icon="heart" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="mb-2">{{ __('No donations found') }}</flux:heading>
            <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your filters to see more results.') }}
                @else
                    {{ __('You haven\'t made any donations yet. Start giving today!') }}
                @endif
            </flux:text>
            <flux:button variant="primary" :href="route('giving.form', $branch)" wire:navigate>
                {{ __('Make a Donation') }}
            </flux:button>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->donations as $donation)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        {{-- Left: Date & Type --}}
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                <flux:icon icon="heart" class="size-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm">
                                        GHS {{ number_format((float) $donation->amount, 2) }}
                                    </flux:heading>
                                    @if($donation->is_recurring)
                                        <flux:badge color="purple" size="sm">{{ __('Recurring') }}</flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ ucwords(str_replace('_', ' ', $donation->donation_type->value)) }}
                                    &middot;
                                    {{ $donation->donation_date?->format('M d, Y') }}
                                </flux:text>
                            </div>
                        </div>

                        {{-- Right: Payment Method & Actions --}}
                        <div class="flex items-center gap-3">
                            <flux:badge color="zinc" size="sm">
                                {{ ucwords(str_replace('_', ' ', $donation->payment_method->value)) }}
                            </flux:badge>

                            @if($donation->canSendReceipt())
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="document-arrow-down"
                                    wire:click="downloadReceipt('{{ $donation->id }}')"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('Receipt') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    {{-- Notes (if any) --}}
                    @if($donation->notes)
                        <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $donation->notes }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Recurring Donations Section --}}
    @if($this->recurringDonations->isNotEmpty())
        <div class="mt-8">
            <flux:heading size="lg" class="mb-4">{{ __('Recurring Donations') }}</flux:heading>

            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->recurringDonations as $recurring)
                        <div class="flex items-center justify-between p-4">
                            <div>
                                <flux:heading size="sm">
                                    GHS {{ number_format((float) $recurring->amount, 2) }} / {{ $recurring->recurring_interval }}
                                </flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ ucwords(str_replace('_', ' ', $recurring->donation_type->value)) }}
                                    &middot;
                                    {{ __('Started') }} {{ $recurring->created_at->format('M d, Y') }}
                                </flux:text>
                            </div>
                            <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('To cancel a recurring donation, please contact the church office.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    @endif
</section>
