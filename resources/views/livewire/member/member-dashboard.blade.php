<div>
    {{-- Welcome Section --}}
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Welcome back, :name!', ['name' => $this->member->first_name]) }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Here\'s an overview of your activity at :branch.', ['branch' => $this->member->primaryBranch?->name]) }}
        </flux:text>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Giving This Year --}}
        <flux:card class="!p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon name="banknotes" class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Giving This Year') }}</flux:text>
                    <flux:heading size="lg">{{ $this->currency->symbol() }}{{ number_format($this->totalGivingThisYear, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        {{-- Giving This Month --}}
        <flux:card class="!p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="calendar" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Giving This Month') }}</flux:text>
                    <flux:heading size="lg">{{ $this->currency->symbol() }}{{ number_format($this->totalGivingThisMonth, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        {{-- Attendance This Month --}}
        <flux:card class="!p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon name="calendar-days" class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Attendance This Month') }}</flux:text>
                    <flux:heading size="lg">{{ $this->attendanceThisMonth }} {{ __('services') }}</flux:heading>
                </div>
            </div>
        </flux:card>

        {{-- Attendance This Year --}}
        <flux:card class="!p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="chart-bar" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Attendance This Year') }}</flux:text>
                    <flux:heading size="lg">{{ $this->attendanceThisYear }} {{ __('services') }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Recent Attendance --}}
        <flux:card>
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Recent Attendance') }}</flux:heading>
                <flux:button href="{{ route('member.attendance') }}" variant="ghost" size="sm" wire:navigate>
                    {{ __('View All') }}
                </flux:button>
            </div>

            @if($this->recentAttendance->isEmpty())
                <div class="p-6 text-center text-zinc-500">
                    {{ __('No attendance records yet.') }}
                </div>
            @else
                <flux:table>
                    <flux:table.rows>
                        @foreach($this->recentAttendance as $attendance)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div>
                                        <div class="font-medium">{{ $attendance->service?->name ?? __('Service') }}</div>
                                        <div class="text-sm text-zinc-500">{{ $attendance->date->format('M d, Y') }}</div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <flux:badge color="green" size="sm">{{ __('Present') }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        {{-- Recent Donations --}}
        <flux:card>
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Recent Giving') }}</flux:heading>
                <flux:button href="{{ route('member.giving') }}" variant="ghost" size="sm" wire:navigate>
                    {{ __('View All') }}
                </flux:button>
            </div>

            @if($this->recentDonations->isEmpty())
                <div class="p-6 text-center text-zinc-500">
                    {{ __('No giving records yet.') }}
                </div>
            @else
                <flux:table>
                    <flux:table.rows>
                        @foreach($this->recentDonations as $donation)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div>
                                        <div class="font-medium">{{ $this->currency->symbol() }}{{ number_format($donation->amount, 2) }}</div>
                                        <div class="text-sm text-zinc-500">{{ $donation->donation_date->format('M d, Y') }}</div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    @if($donation->category)
                                        <flux:badge size="sm">{{ $donation->category }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        {{-- Active Pledges --}}
        @if($this->activePledges->isNotEmpty())
            <flux:card class="lg:col-span-2">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('My Pledges') }}</flux:heading>
                    <flux:button href="{{ route('member.pledges') }}" variant="ghost" size="sm" wire:navigate>
                        {{ __('View All') }}
                    </flux:button>
                </div>

                <div class="space-y-4 p-4">
                    @foreach($this->pledgeProgress as $progress)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ $progress['pledge']->campaign?->name ?? __('Pledge') }}</div>
                                    <div class="text-sm text-zinc-500">
                                        {{ __('Target: :amount', ['amount' => $this->currency->symbol() . number_format($progress['pledge']->amount, 2)]) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-green-600">{{ $progress['percentage'] }}%</div>
                                    <div class="text-sm text-zinc-500">{{ __('completed') }}</div>
                                </div>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div
                                    class="h-full rounded-full bg-green-500 transition-all"
                                    style="width: {{ $progress['percentage'] }}%"
                                ></div>
                            </div>
                            <div class="mt-2 flex justify-between text-sm text-zinc-500">
                                <span>{{ __('Paid: :amount', ['amount' => $this->currency->symbol() . number_format($progress['paid'], 2)]) }}</span>
                                <span>{{ __('Remaining: :amount', ['amount' => $this->currency->symbol() . number_format($progress['remaining'], 2)]) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8">
        <flux:heading size="lg" class="mb-4">{{ __('Quick Actions') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:button href="{{ route('giving.form', $this->member->primaryBranch) }}" variant="primary" icon="heart" class="justify-center">
                {{ __('Give Now') }}
            </flux:button>
            <flux:button href="{{ route('member.giving') }}" variant="filled" icon="document-arrow-down" wire:navigate class="justify-center">
                {{ __('Download Giving Statement') }}
            </flux:button>
            <flux:button href="{{ route('member.profile') }}" variant="filled" icon="user" wire:navigate class="justify-center">
                {{ __('Update Profile') }}
            </flux:button>
            <flux:button href="{{ route('member.pledges') }}" variant="filled" icon="clipboard-document-check" wire:navigate class="justify-center">
                {{ __('View Pledges') }}
            </flux:button>
        </div>
    </div>
</div>
