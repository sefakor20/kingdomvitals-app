<section class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Report Center') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
            {{ __('Access all reports and analytics for :branch', ['branch' => $branch->name]) }}
        </flux:text>
    </div>

    <!-- Report Categories Grid -->
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Membership Reports -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.user-group class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <flux:heading size="lg">{{ __('Membership') }}</flux:heading>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->membershipStats['total']) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Active Members') }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ $this->membershipStats['new_this_month'] }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</div>
                </div>
            </div>

            <div class="space-y-2">
                <a href="{{ route('reports.membership.directory', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Member Directory') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.membership.new-members', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('New Members Report') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.membership.inactive', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Inactive Members') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.membership.demographics', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Demographics') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.membership.growth', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Growth Trends') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
            </div>
        </div>

        <!-- Attendance Reports -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.clipboard-document-check class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <flux:heading size="lg">{{ __('Attendance') }}</flux:heading>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->attendanceStats['last_sunday']) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Last Sunday') }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->attendanceStats['this_month']) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</div>
                </div>
            </div>

            <div class="space-y-2">
                <a href="{{ route('reports.attendance.weekly', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Weekly Summary') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.attendance.monthly', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Monthly Comparison') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.attendance.by-service', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('By Service Type') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.attendance.absent-members', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Absent Members') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('reports.attendance.visitors', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('First-time Visitors') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
            </div>
        </div>

        <!-- Financial Reports -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                    <flux:icon.banknotes class="size-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <flux:heading size="lg">{{ __('Financial') }}</flux:heading>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">GHS {{ number_format($this->financialStats['this_month'], 0) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">GHS {{ number_format($this->financialStats['ytd'], 0) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Year to Date') }}</div>
                </div>
            </div>

            <div class="space-y-2">
                <a href="{{ route('finance.dashboard', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Finance Dashboard') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('finance.reports', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Financial Reports') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
                <a href="{{ route('finance.donor-engagement', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('Donor Engagement') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
            </div>
        </div>

        <!-- Communication Reports -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon.chat-bubble-left-right class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <flux:heading size="lg">{{ __('Communication') }}</flux:heading>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->visitorStats['this_month']) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Visitors This Month') }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->visitorStats['converted'] }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Converted') }}</div>
                </div>
            </div>

            <div class="space-y-2">
                <a href="{{ route('sms.analytics', $branch) }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ __('SMS Analytics') }}</span>
                    <flux:icon.chevron-right class="size-4 text-zinc-400" />
                </a>
            </div>
        </div>
    </div>
</section>
