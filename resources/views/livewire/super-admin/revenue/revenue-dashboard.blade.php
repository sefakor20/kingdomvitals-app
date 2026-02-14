<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Revenue Dashboard') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ __('Subscription revenue overview and analytics') }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
            {{ __('Export CSV') }}
        </flux:button>
    </div>

    <!-- Key Revenue Metrics -->
    <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <!-- MRR -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                    <flux:icon.currency-dollar class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('MRR') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['mrr'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- ARR -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                    <flux:icon.chart-bar class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ARR') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['arr'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Active Subscribers -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900">
                    <flux:icon.user-group class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Subscribers') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['activeCount'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Trial Tenants -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                    <flux:icon.clock class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('In Trial') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['trialCount'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                    <flux:icon.arrow-trending-up class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Conversion Rate') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['conversionRate'] }}%</flux:heading>
                </div>
            </div>
        </div>

        <!-- Churned -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900">
                    <flux:icon.user-minus class="size-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Churned') }}</flux:text>
                    <flux:heading size="lg">{{ $metrics['churnCount'] }}</flux:heading>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Plan Distribution -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Plan Distribution') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Active subscribers by plan') }}</flux:text>
            </div>
            <div class="p-6">
                @forelse($planDistribution as $data)
                    <div wire:key="plan-dist-{{ $data['plan']->id }}" class="mb-4 last:mb-0">
                        <div class="mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">{{ $data['plan']->name }}</flux:text>
                                <flux:badge color="zinc" size="sm">{{ $data['tenantCount'] }} {{ __('tenants') }}</flux:badge>
                            </div>
                            <flux:text class="font-semibold">{{ $data['revenueFormatted'] }}/{{ __('mo') }}</flux:text>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-2 rounded-full bg-indigo-600 transition-all duration-300"
                                style="width: {{ $data['percentage'] }}%"
                            ></div>
                        </div>
                        <flux:text class="mt-1 text-xs text-zinc-500">{{ $data['percentage'] }}% {{ __('of active subscribers') }}</flux:text>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:text class="text-zinc-500">{{ __('No active plans found') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Monthly Trends -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __("This Month's Trends") }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ now()->format('F Y') }}</flux:text>
            </div>
            <div class="space-y-6 p-6">
                <!-- New Subscribers -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                            <flux:icon.user-plus class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ __('New Subscribers') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ __('Added this month') }}</flux:text>
                        </div>
                    </div>
                    <flux:heading size="lg" class="text-green-600">+{{ $trends['newThisMonth'] }}</flux:heading>
                </div>

                <!-- Churned -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900">
                            <flux:icon.user-minus class="size-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ __('Churned') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ __('Lost this month') }}</flux:text>
                        </div>
                    </div>
                    <flux:heading size="lg" class="text-red-600">-{{ $trends['churnedThisMonth'] }}</flux:heading>
                </div>

                <!-- Net Growth -->
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $trends['netGrowth'] >= 0 ? 'bg-blue-100 dark:bg-blue-900' : 'bg-orange-100 dark:bg-orange-900' }}">
                                <flux:icon.chart-bar class="size-5 {{ $trends['netGrowth'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}" />
                            </div>
                            <div>
                                <flux:text class="font-medium">{{ __('Net Growth') }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">{{ __('Overall change') }}</flux:text>
                            </div>
                        </div>
                        <flux:heading size="lg" class="{{ $trends['netGrowth'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                            {{ $trends['netGrowth'] >= 0 ? '+' : '' }}{{ $trends['netGrowth'] }}
                        </flux:heading>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Plan Table -->
    <div class="mt-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Revenue Breakdown by Plan') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('Detailed monthly revenue contribution') }}</flux:text>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Plan') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Monthly Price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Active Subscribers') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Monthly Revenue') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('% of Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($planDistribution as $data)
                        <tr wire:key="plan-row-{{ $data['plan']->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-medium">{{ $data['plan']->name }}</flux:text>
                                    @if($data['plan']->is_default)
                                        <flux:badge color="indigo" size="sm">{{ __('Default') }}</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ Number::currency($data['plan']->price_monthly, in: $this->baseCurrency->code()) }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $data['tenantCount'] }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-semibold">{{ $data['revenueFormatted'] }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="{{ $data['percentage'] > 30 ? 'green' : ($data['percentage'] > 10 ? 'yellow' : 'zinc') }}" size="sm">
                                    {{ $data['percentage'] }}%
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center">
                                <flux:text class="text-zinc-500">{{ __('No plans found') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
