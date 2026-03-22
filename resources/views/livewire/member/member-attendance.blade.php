<div>
    <div class="mb-8">
        <flux:heading size="xl">{{ __('My Attendance') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('View your attendance history.') }}
        </flux:text>
    </div>

    {{-- Year Selector --}}
    <div class="mb-6 flex items-center gap-4">
        <flux:text class="font-medium">{{ __('Year:') }}</flux:text>
        <flux:select wire:model.live="year" class="w-32">
            @foreach($this->availableYears as $y)
                <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Summary Card --}}
    <flux:card class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
            <div>
                <flux:text class="text-sm text-zinc-500">{{ __('Total Attendance in :year', ['year' => $year]) }}</flux:text>
                <flux:heading size="xl">{{ $this->yearlyTotal }} {{ __('services') }}</flux:heading>
            </div>
        </div>
    </flux:card>

    {{-- Monthly Breakdown --}}
    <flux:card class="mb-6">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Monthly Breakdown') }}</flux:heading>
        </div>
        <div class="grid grid-cols-4 gap-2 p-4 sm:grid-cols-6 lg:grid-cols-12">
            @foreach($this->monthlyTotals as $month => $total)
                <div class="rounded-lg bg-zinc-50 p-2 text-center dark:bg-zinc-800">
                    <div class="text-xs text-zinc-500">{{ date('M', mktime(0, 0, 0, $month, 1)) }}</div>
                    <div class="text-sm font-medium">{{ $total }}</div>
                </div>
            @endforeach
        </div>
    </flux:card>

    {{-- Attendance Table --}}
    <flux:card>
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Attendance History') }}</flux:heading>
        </div>

        @if($this->attendance->isEmpty())
            <div class="p-8 text-center text-zinc-500">
                {{ __('No attendance records for :year.', ['year' => $year]) }}
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Service') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->attendance as $record)
                        <flux:table.row>
                            <flux:table.cell>{{ $record->date->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $record->service?->name ?? __('Service') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="green" size="sm">{{ __('Present') }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="p-4">
                {{ $this->attendance->links() }}
            </div>
        @endif
    </flux:card>
</div>
