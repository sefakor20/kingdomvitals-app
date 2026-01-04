<div wire:poll.5s="refreshStats">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Attendance Dashboard') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ $service->name }} - {{ $branch->name }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:input
                type="date"
                wire:model.live="selectedDate"
                class="w-40"
            />
            <flux:button
                href="{{ route('attendance.live-check-in', ['branch' => $branch, 'service' => $service]) }}"
                variant="primary"
            >
                {{ __('Check-In Kiosk') }}
            </flux:button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Attendance --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ __('Total Attendance') }}
                </flux:text>
                @if ($this->stats['percentChange'] != 0)
                    <flux:badge
                        :color="$this->stats['percentChange'] > 0 ? 'green' : 'red'"
                        size="sm"
                    >
                        {{ $this->stats['percentChange'] > 0 ? '+' : '' }}{{ $this->stats['percentChange'] }}%
                    </flux:badge>
                @endif
            </div>
            <div class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['total']) }}
            </div>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Last week: :count', ['count' => number_format($this->stats['lastWeek'])]) }}
            </flux:text>
        </div>

        {{-- Members --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ __('Members') }}
            </flux:text>
            <div class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['members']) }}
            </div>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($this->stats['total'] > 0)
                    {{ round(($this->stats['members'] / $this->stats['total']) * 100) }}% {{ __('of total') }}
                @else
                    {{ __('No check-ins yet') }}
                @endif
            </flux:text>
        </div>

        {{-- Visitors --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ __('Visitors') }}
            </flux:text>
            <div class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['visitors']) }}
            </div>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($this->stats['total'] > 0)
                    {{ round(($this->stats['visitors'] / $this->stats['total']) * 100) }}% {{ __('of total') }}
                @else
                    {{ __('No check-ins yet') }}
                @endif
            </flux:text>
        </div>

        {{-- Capacity --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ __('Capacity') }}
            </flux:text>
            <div class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                {{ $this->stats['capacityPercent'] }}%
            </div>
            @if ($this->stats['capacity'] > 0)
                <div class="mt-3">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $this->stats['capacityPercent'] > 90 ? 'bg-red-500' : ($this->stats['capacityPercent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                            style="width: {{ $this->stats['capacityPercent'] }}%"
                        ></div>
                    </div>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ number_format($this->stats['total']) }} / {{ number_format($this->stats['capacity']) }}
                    </flux:text>
                </div>
            @else
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No capacity set') }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Recent Check-ins --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Check-ins') }}</flux:heading>

            @if ($this->recentCheckIns->isEmpty())
                <div class="py-8 text-center">
                    <flux:icon name="users" class="mx-auto mb-2 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('No check-ins yet') }}
                    </flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($this->recentCheckIns as $checkIn)
                        <div
                            wire:key="recent-{{ $checkIn['id'] }}"
                            class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-700/50"
                        >
                            <div class="flex items-center gap-3">
                                @if ($checkIn['photo_url'])
                                    <img
                                        src="{{ $checkIn['photo_url'] }}"
                                        alt="{{ $checkIn['name'] }}"
                                        class="h-10 w-10 rounded-full object-cover"
                                    />
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                        <flux:icon name="user" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                    </div>
                                @endif
                                <div>
                                    <flux:text class="font-medium text-zinc-900 dark:text-white">
                                        {{ $checkIn['name'] }}
                                    </flux:text>
                                    <div class="flex items-center gap-2">
                                        <flux:badge
                                            :color="$checkIn['type'] === 'member' ? 'blue' : 'orange'"
                                            size="sm"
                                        >
                                            {{ $checkIn['type'] === 'member' ? __('Member') : __('Visitor') }}
                                        </flux:badge>
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ ucfirst($checkIn['method']) }}
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                {{ $checkIn['time'] }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Check-ins by Hour --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Check-ins by Hour') }}</flux:heading>

            @php
                $hourlyData = $this->checkInsByHour;
                $maxValue = max(1, max($hourlyData));
            @endphp

            <div class="flex h-48 items-end justify-between gap-1">
                @foreach ($hourlyData as $hour => $count)
                    <div class="flex flex-1 flex-col items-center">
                        <div
                            class="w-full rounded-t bg-blue-500 transition-all duration-300"
                            style="height: {{ ($count / $maxValue) * 100 }}%"
                            title="{{ $count }} {{ __('check-ins at') }} {{ sprintf('%02d:00', $hour) }}"
                        ></div>
                        @if ($hour % 3 === 0 || $hour === 22)
                            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ sprintf('%d', $hour) }}
                            </flux:text>
                        @else
                            <div class="mt-1 h-4"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4 text-center">
                @php
                    $peakHour = array_search(max($hourlyData), $hourlyData);
                    $peakCount = max($hourlyData);
                @endphp
                @if ($peakCount > 0)
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Peak time: :hour:00 with :count check-ins', ['hour' => sprintf('%02d', $peakHour), 'count' => $peakCount]) }}
                    </flux:text>
                @else
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No check-ins recorded') }}
                    </flux:text>
                @endif
            </div>
        </div>
    </div>
</div>
