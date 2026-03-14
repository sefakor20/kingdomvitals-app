<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/50">
                <flux:icon icon="banknotes" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:heading size="xl">{{ __('Giving Intelligence') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Analyze giving capacity and pledge fulfillment risk for :branch', ['branch' => $branch->name]) }}
                </flux:text>
            </div>
        </div>
        <flux:badge color="purple">{{ __('AI-Powered') }}</flux:badge>
    </div>

    {{-- View Switcher --}}
    <div class="flex gap-2">
        <flux:button
            wire:click="switchView('capacity')"
            :variant="$view === 'capacity' ? 'primary' : 'ghost'"
            size="sm"
            icon="chart-pie"
        >
            {{--  <flux:icon icon="chart-pie" class="size-4" />  --}}
            {{ __('Giving Capacity') }}
        </flux:button>
        <flux:button
            wire:click="switchView('pledges')"
            :variant="$view === 'pledges' ? 'primary' : 'ghost'"
            size="sm"
            icon="exclamation-triangle"
        >
            {{ __('Pledge Risk') }}
        </flux:button>
    </div>

    @if($view === 'capacity')
        {{-- Giving Capacity View --}}
        @if(!$this->capacityFeatureEnabled)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/20">
                <div class="flex items-center gap-3">
                    <flux:icon icon="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                    <div>
                        <flux:heading size="base">{{ __('Feature Disabled') }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('Giving capacity analysis is currently disabled. Enable it in AI settings.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @else
            {{-- Capacity Stats --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Members Analyzed') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1">{{ number_format($this->capacityStats['total_analyzed']) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Potential Gap') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1 text-emerald-600 dark:text-emerald-400">
                        GHS {{ number_format($this->capacityStats['total_potential_gap'], 0) }}
                    </flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg. Utilization') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1">{{ $this->capacityStats['avg_capacity_score'] }}%</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Under-Utilized') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1 text-amber-600 dark:text-amber-400">{{ number_format($this->capacityStats['under_utilized']) }}</flux:heading>
                </div>
            </div>

            {{-- Utilization Distribution --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="base">{{ __('Capacity Utilization Distribution') }}</flux:heading>
                    <flux:button wire:click="runCapacityAnalysis" variant="ghost" size="sm" icon="arrow-path">
                        {{ __('Refresh Analysis') }}
                    </flux:button>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-red-600 dark:text-red-400">{{ __('Under-utilized (<40%)') }}</span>
                            <span class="font-medium">{{ $this->capacityStats['under_utilized'] }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            @php $total = max(1, $this->capacityStats['total_analyzed']); @endphp
                            <div class="h-full bg-red-500" style="width: {{ ($this->capacityStats['under_utilized'] / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-amber-600 dark:text-amber-400">{{ __('Moderate (40-70%)') }}</span>
                            <span class="font-medium">{{ $this->capacityStats['moderately_utilized'] }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full bg-amber-500" style="width: {{ ($this->capacityStats['moderately_utilized'] / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-green-600 dark:text-green-400">{{ __('Well-utilized (70%+)') }}</span>
                            <span class="font-medium">{{ $this->capacityStats['well_utilized'] }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full bg-green-500" style="width: {{ ($this->capacityStats['well_utilized'] / $total) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search & Filters --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search members...') }}" size="sm" class="w-64" />
                    @if($search !== '')
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                            <flux:icon icon="x-mark" class="size-4" />
                            {{ __('Reset') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            {{-- High Potential Members Table --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="base">{{ __('High Potential Members') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ __('Members giving below their estimated capacity') }}</flux:text>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Utilization') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Potential Gap') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Profession') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Last Analyzed') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($this->highPotentialMembers as $member)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-8 items-center justify-center rounded-full bg-zinc-200 text-sm font-medium dark:bg-zinc-700">
                                                {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                                            </div>
                                            <flux:text class="font-medium">{{ $member->fullName() }}</flux:text>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full rounded-full {{ $member->giving_capacity_score >= 70 ? 'bg-green-500' : ($member->giving_capacity_score >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                     style="width: {{ min($member->giving_capacity_score, 100) }}%"></div>
                                            </div>
                                            <flux:text class="text-sm font-medium">{{ number_format($member->giving_capacity_score, 0) }}%</flux:text>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text class="font-medium text-emerald-600 dark:text-emerald-400">
                                            GHS {{ number_format($member->giving_potential_gap, 0) }}
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text class="text-sm">{{ $member->profession ?? '-' }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text class="text-sm text-zinc-500">
                                            {{ $member->giving_capacity_analyzed_at?->diffForHumans() ?? '-' }}
                                        </flux:text>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <flux:icon icon="chart-pie" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                                        <flux:text class="mt-2 text-zinc-500">{{ __('No capacity data available.') }}</flux:text>
                                        <flux:button wire:click="runCapacityAnalysis" variant="ghost" size="sm" class="mt-4">
                                            {{ __('Run Analysis') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($this->highPotentialMembers->hasPages())
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        {{ $this->highPotentialMembers->links() }}
                    </div>
                @endif
            </div>
        @endif
    @else
        {{-- Pledge Risk View --}}
        @if(!$this->pledgeFeatureEnabled)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/20">
                <div class="flex items-center gap-3">
                    <flux:icon icon="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                    <div>
                        <flux:heading size="base">{{ __('Feature Disabled') }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('Pledge prediction is currently disabled. Enable it in AI settings.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @else
            {{-- Pledge Stats --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Predictions') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1">{{ number_format($this->pledgeStats['total']) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('High Risk') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1 text-red-600 dark:text-red-400">{{ number_format($this->pledgeStats['high_risk']) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Medium Risk') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1 text-amber-600 dark:text-amber-400">{{ number_format($this->pledgeStats['medium_risk']) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Low Risk') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1 text-green-600 dark:text-green-400">{{ number_format($this->pledgeStats['low_risk']) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg. Fulfillment') }}</flux:text>
                    <flux:heading size="2xl" class="mt-1">{{ $this->pledgeStats['avg_probability'] }}%</flux:heading>
                </div>
            </div>

            {{-- Filters --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-4">
                    <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search members...') }}" size="sm" class="w-48" />

                    <flux:select wire:model.live="riskFilter" size="sm" class="w-36" placeholder="{{ __('All Risk Levels') }}">
                        <option value="">{{ __('All Risk Levels') }}</option>
                        @foreach($this->availableRiskLevels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    @if($riskFilter !== '' || $search !== '')
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                            <flux:icon icon="x-mark" class="size-4" />
                            {{ __('Reset') }}
                        </flux:button>
                    @endif

                    <div class="ml-auto">
                        <flux:button wire:click="runPledgePrediction" variant="ghost" icon="arrow-path" size="sm">
                            {{ __('Refresh Predictions') }}
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- At-Risk Pledges Table --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Campaign') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Fulfillment') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Risk Level') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Recommended Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($this->atRiskPledges as $prediction)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-8 items-center justify-center rounded-full bg-zinc-200 text-sm font-medium dark:bg-zinc-700">
                                                {{ substr($prediction->member->first_name ?? 'U', 0, 1) }}{{ substr($prediction->member->last_name ?? 'K', 0, 1) }}
                                            </div>
                                            <flux:text class="font-medium">{{ $prediction->member?->fullName() ?? 'Unknown' }}</flux:text>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text class="text-sm">{{ $prediction->pledge?->campaign_name ?? '-' }}</flux:text>
                                        <flux:text class="text-xs text-zinc-500">
                                            {{ number_format($prediction->pledge?->amount ?? 0, 2) }} GHS
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full rounded-full {{ $prediction->fulfillment_probability >= 70 ? 'bg-green-500' : ($prediction->fulfillment_probability >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                     style="width: {{ min($prediction->fulfillment_probability, 100) }}%"></div>
                                            </div>
                                            <flux:text class="text-sm font-medium">{{ number_format($prediction->fulfillment_probability, 0) }}%</flux:text>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:badge :color="$prediction->risk_level->color()">
                                            {{ $prediction->risk_level->label() }}
                                        </flux:badge>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if($prediction->recommended_nudge_at)
                                            <flux:text class="text-sm">
                                                {{ __('Nudge by :date', ['date' => $prediction->recommended_nudge_at->format('M j')]) }}
                                            </flux:text>
                                        @else
                                            <flux:text class="text-sm text-zinc-400">{{ __('No action needed') }}</flux:text>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <flux:icon icon="shield-check" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                                        <flux:text class="mt-2 text-zinc-500">{{ __('No pledge predictions available.') }}</flux:text>
                                        <flux:button wire:click="runPledgePrediction" variant="ghost" size="sm" class="mt-4">
                                            {{ __('Generate Predictions') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($this->atRiskPledges->hasPages())
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        {{ $this->atRiskPledges->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
