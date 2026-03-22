<div>
    <div class="mb-8">
        <flux:heading size="xl">{{ __('My Pledges') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Track your pledge commitments and progress.') }}
        </flux:text>
    </div>

    {{-- Active Pledges --}}
    <flux:card class="mb-6">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Active Pledges') }}</flux:heading>
        </div>

        @if($this->activePledges->isEmpty())
            <div class="p-8 text-center text-zinc-500">
                {{ __('You have no active pledges.') }}
            </div>
        @else
            <div class="space-y-4 p-4">
                @foreach($this->pledgeProgress as $progress)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <flux:heading size="lg">{{ $progress['pledge']->campaign?->name ?? __('Pledge') }}</flux:heading>
                                @if($progress['pledge']->campaign?->description)
                                    <flux:text class="text-sm text-zinc-500">{{ Str::limit($progress['pledge']->campaign->description, 100) }}</flux:text>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-green-600">{{ $progress['percentage'] }}%</div>
                                <flux:text class="text-sm text-zinc-500">{{ __('complete') }}</flux:text>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mb-3 h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-full rounded-full bg-green-500 transition-all"
                                style="width: {{ $progress['percentage'] }}%"
                            ></div>
                        </div>

                        <div class="flex flex-wrap justify-between gap-4 text-sm">
                            <div>
                                <span class="text-zinc-500">{{ __('Pledged:') }}</span>
                                <span class="font-medium">{{ $this->currency->symbol() }}{{ number_format($progress['pledge']->amount, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500">{{ __('Paid:') }}</span>
                                <span class="font-medium text-green-600">{{ $this->currency->symbol() }}{{ number_format($progress['paid'], 2) }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500">{{ __('Remaining:') }}</span>
                                <span class="font-medium text-amber-600">{{ $this->currency->symbol() }}{{ number_format($progress['remaining'], 2) }}</span>
                            </div>
                        </div>

                        @if($progress['pledge']->end_date)
                            <div class="mt-3 text-sm text-zinc-500">
                                <flux:icon name="calendar" class="inline size-4" />
                                {{ __('Due by :date', ['date' => $progress['pledge']->end_date->format('M d, Y')]) }}
                            </div>
                        @endif

                        @if($progress['remaining'] > 0)
                            <div class="mt-4">
                                <flux:button href="{{ route('giving.form', $this->member->primaryBranch) }}" variant="primary" size="sm" icon="heart">
                                    {{ __('Make a Payment') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Completed Pledges --}}
    @if($this->completedPledges->isNotEmpty())
        <flux:card>
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Completed Pledges') }}</flux:heading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Campaign') }}</flux:table.column>
                    <flux:table.column>{{ __('Amount') }}</flux:table.column>
                    <flux:table.column>{{ __('End Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->completedPledges as $pledge)
                        <flux:table.row>
                            <flux:table.cell>{{ $pledge->campaign?->name ?? __('Pledge') }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $this->currency->symbol() }}{{ number_format($pledge->amount, 2) }}</flux:table.cell>
                            <flux:table.cell>{{ $pledge->end_date?->format('M d, Y') ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">{{ __('Completed') }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
