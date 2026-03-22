<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Email Analytics') }}</flux:heading>
            <flux:subheading>{{ __('Track email performance and engagement') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('email.index', $branch)" icon="arrow-left" wire:navigate>
                {{ __('Back to Emails') }}
            </flux:button>
        </div>
    </div>

    <!-- Period Selection -->
    <div class="mb-6 flex flex-wrap gap-2">
        <flux:button
            variant="{{ $period === 7 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(7)"
        >
            {{ __('7 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 30 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(30)"
        >
            {{ __('30 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 90 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(90)"
        >
            {{ __('90 Days') }}
        </flux:button>
    </div>

    <!-- Summary Stats -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Sent') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Delivered') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['delivered']) }}</flux:heading>
            <flux:text class="text-xs text-green-600">{{ $this->summaryStats['delivery_rate'] }}% {{ __('rate') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Opened') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['opened']) }}</flux:heading>
            <flux:text class="text-xs text-purple-600">{{ $this->summaryStats['open_rate'] }}% {{ __('rate') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Clicked') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['clicked']) }}</flux:heading>
            <flux:text class="text-xs text-blue-600">{{ $this->summaryStats['click_rate'] }}% {{ __('rate') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Bounced/Failed') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['bounced'] + $this->summaryStats['failed']) }}</flux:heading>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Delivery Rate Over Time -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Delivery Rate Over Time') }}</flux:heading>
            <div class="h-64">
                @if(count($this->deliveryRateData['labels']) > 0)
                    <canvas
                        id="deliveryRateChart"
                        x-data
                        x-init="
                            new Chart($el, {
                                type: 'line',
                                data: {
                                    labels: @js($this->deliveryRateData['labels']),
                                    datasets: [{
                                        label: '{{ __('Delivery Rate %') }}',
                                        data: @js($this->deliveryRateData['data']),
                                        borderColor: 'rgb(34, 197, 94)',
                                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                        fill: true,
                                        tension: 0.3
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    }
                                }
                            })
                        "
                    ></canvas>
                @else
                    <div class="flex h-full items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No data available') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Open & Click Rates Over Time -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Engagement Over Time') }}</flux:heading>
            <div class="h-64">
                @if(count($this->openRateData['labels']) > 0)
                    <canvas
                        id="engagementChart"
                        x-data
                        x-init="
                            new Chart($el, {
                                type: 'line',
                                data: {
                                    labels: @js($this->openRateData['labels']),
                                    datasets: [
                                        {
                                            label: '{{ __('Open Rate %') }}',
                                            data: @js($this->openRateData['openData']),
                                            borderColor: 'rgb(147, 51, 234)',
                                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                                            fill: false,
                                            tension: 0.3
                                        },
                                        {
                                            label: '{{ __('Click Rate %') }}',
                                            data: @js($this->openRateData['clickData']),
                                            borderColor: 'rgb(59, 130, 246)',
                                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                            fill: false,
                                            tension: 0.3
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    }
                                }
                            })
                        "
                    ></canvas>
                @else
                    <div class="flex h-full items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No data available') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Emails by Type -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Emails by Type') }}</flux:heading>
            <div class="h-64">
                @if(count($this->messagesByTypeData['labels']) > 0)
                    <canvas
                        id="typeChart"
                        x-data
                        x-init="
                            new Chart($el, {
                                type: 'doughnut',
                                data: {
                                    labels: @js($this->messagesByTypeData['labels']),
                                    datasets: [{
                                        data: @js($this->messagesByTypeData['data']),
                                        backgroundColor: [
                                            'rgb(236, 72, 153)',
                                            'rgb(234, 179, 8)',
                                            'rgb(59, 130, 246)',
                                            'rgb(147, 51, 234)',
                                            'rgb(34, 197, 94)',
                                            'rgb(249, 115, 22)',
                                            'rgb(99, 102, 241)',
                                            'rgb(113, 113, 122)'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            })
                        "
                    ></canvas>
                @else
                    <div class="flex h-full items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No data available') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Status Distribution') }}</flux:heading>
            <div class="h-64">
                @if(count($this->statusDistributionData['labels']) > 0)
                    <canvas
                        id="statusChart"
                        x-data
                        x-init="
                            new Chart($el, {
                                type: 'pie',
                                data: {
                                    labels: @js($this->statusDistributionData['labels']),
                                    datasets: [{
                                        data: @js($this->statusDistributionData['data']),
                                        backgroundColor: @js($this->statusDistributionData['colors'])
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            })
                        "
                    ></canvas>
                @else
                    <div class="flex h-full items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No data available') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
