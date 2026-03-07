<div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('System Logs') }}</flux:heading>
            <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
                {{ __('View Laravel application logs and errors') }}
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export CSV') }}
            </flux:button>
            @if($canClearLogs)
                <flux:button variant="danger" icon="trash" wire:click="confirmClearLogs">
                    {{ __('Clear Old Logs') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:flex lg:flex-row lg:items-center lg:flex-wrap">
        <!-- Log File Select -->
        <div class="sm:col-span-2 lg:w-56">
            <flux:select wire:model.live="logFile">
                @foreach($logFiles as $file)
                    <option value="{{ $file['name'] }}">
                        {{ $file['name'] }} ({{ Number::fileSize($file['size']) }})
                    </option>
                @endforeach
            </flux:select>
        </div>

        <!-- Level Filter -->
        <div class="lg:w-40">
            <flux:select wire:model.live="level">
                <option value="">{{ __('All Levels') }}</option>
                @foreach($levels as $levelOption)
                    <option value="{{ $levelOption }}">{{ ucfirst($levelOption) }}</option>
                @endforeach
            </flux:select>
        </div>

        <!-- Search -->
        <div class="sm:col-span-2 lg:col-span-1 lg:flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search log messages...') }}"
                icon="magnifying-glass"
            />
        </div>

        <!-- Date Range -->
        <div class="lg:w-40">
            <flux:input
                wire:model.live="startDate"
                type="date"
                placeholder="{{ __('Start Date') }}"
            />
        </div>
        <div class="lg:w-40">
            <flux:input
                wire:model.live="endDate"
                type="date"
                placeholder="{{ __('End Date') }}"
            />
        </div>
    </div>

    @php
        $levelColors = [
            'emergency' => 'red',
            'alert' => 'red',
            'critical' => 'red',
            'error' => 'amber',
            'warning' => 'yellow',
            'notice' => 'blue',
            'info' => 'cyan',
            'debug' => 'zinc',
        ];
    @endphp

    @if($fileTooLarge)
        <!-- File Too Large Warning -->
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400" />
                <div>
                    <flux:heading size="sm" class="text-amber-800 dark:text-amber-200">
                        {{ __('Log file too large') }}
                    </flux:heading>
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('The selected log file exceeds 50MB. Please download it directly or select a smaller file.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    @else
        <!-- Mobile Card View -->
        <div class="space-y-3 md:hidden">
            @forelse($logs as $index => $log)
                @php
                    $levelColor = $levelColors[strtolower($log['level'])] ?? 'zinc';
                    $isExpanded = $expandedEntry === $index;
                    $hasDetails = $log['stackTrace'] || $log['context'];
                @endphp
                <div
                    wire:key="mobile-log-{{ $index }}"
                    class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden"
                >
                    <!-- Card Header - Always Visible -->
                    <button
                        type="button"
                        wire:click="toggleExpand({{ $index }})"
                        class="w-full px-4 py-3 text-left {{ $hasDetails ? 'cursor-pointer' : 'cursor-default' }}"
                        {{ !$hasDetails ? 'disabled' : '' }}
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:badge color="{{ $levelColor }}" size="sm">
                                        {{ strtoupper($log['level']) }}
                                    </flux:badge>
                                    <flux:text class="text-xs text-slate-500">
                                        {{ $log['timestamp']->format('M d, H:i:s') }}
                                    </flux:text>
                                </div>
                                <flux:text class="text-sm {{ $isExpanded ? '' : 'line-clamp-2' }}">
                                    {{ $isExpanded ? $log['message'] : Str::limit($log['message'], 120) }}
                                </flux:text>
                            </div>
                            @if($hasDetails)
                                <flux:icon
                                    name="{{ $isExpanded ? 'chevron-up' : 'chevron-down' }}"
                                    class="size-5 text-slate-400 flex-shrink-0"
                                />
                            @endif
                        </div>
                    </button>

                    <!-- Expanded Details -->
                    @if($isExpanded && $hasDetails)
                        <div class="border-t border-slate-200 dark:border-slate-700 px-4 py-3 bg-slate-50 dark:bg-slate-900">
                            <div class="space-y-3">
                                @if($log['context'])
                                    <div>
                                        <flux:heading size="sm">{{ __('Context') }}</flux:heading>
                                        <pre class="text-xs bg-slate-800 text-slate-100 p-3 rounded overflow-x-auto mt-1">{{ json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                                @if($log['stackTrace'])
                                    <div>
                                        <flux:heading size="sm">{{ __('Stack Trace') }}</flux:heading>
                                        <pre class="text-xs bg-slate-800 text-slate-100 p-3 rounded overflow-x-auto max-h-64 mt-1">{{ $log['stackTrace'] }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 px-6 py-12 text-center">
                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-slate-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('No log entries found') }}</flux:heading>
                    <flux:text class="mt-2 text-slate-500">
                        @if($search || $level)
                            {{ __('Try adjusting your search or filter criteria') }}
                        @elseif(empty($logFiles))
                            {{ __('No log files available') }}
                        @else
                            {{ __('No logs available in the selected file') }}
                        @endif
                    </flux:text>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 w-36">
                                {{ __('Time') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 w-28">
                                {{ __('Level') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ __('Message') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 w-24">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($logs as $index => $log)
                            @php
                                $levelColor = $levelColors[strtolower($log['level'])] ?? 'zinc';
                                $isExpanded = $expandedEntry === $index;
                                $hasDetails = $log['stackTrace'] || $log['context'];
                            @endphp
                            <tr wire:key="desktop-log-{{ $index }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3 whitespace-nowrap align-top">
                                    <flux:text class="text-sm" title="{{ $log['timestamp']->format('Y-m-d H:i:s') }}">
                                        {{ $log['timestamp']->format('M d, H:i:s') }}
                                    </flux:text>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap align-top">
                                    <flux:badge color="{{ $levelColor }}" size="sm">
                                        {{ strtoupper($log['level']) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <flux:text class="text-sm break-words">
                                        {{ Str::limit($log['message'], 150) }}
                                    </flux:text>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap align-top">
                                    @if($hasDetails)
                                        <flux:button
                                            variant="ghost"
                                            size="xs"
                                            icon="{{ $isExpanded ? 'chevron-up' : 'chevron-down' }}"
                                            wire:click="toggleExpand({{ $index }})"
                                        >
                                            {{ $isExpanded ? __('Hide') : __('Details') }}
                                        </flux:button>
                                    @else
                                        <flux:text class="text-sm text-slate-400">-</flux:text>
                                    @endif
                                </td>
                            </tr>
                            @if($isExpanded && $hasDetails)
                                <tr wire:key="desktop-log-{{ $index }}-details">
                                    <td colspan="4" class="px-4 py-4 bg-slate-100 dark:bg-slate-900">
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between">
                                                <flux:heading size="sm">{{ __('Full Message') }}</flux:heading>
                                                <flux:button
                                                    variant="ghost"
                                                    size="xs"
                                                    icon="x-mark"
                                                    wire:click="toggleExpand({{ $index }})"
                                                >
                                                    {{ __('Close') }}
                                                </flux:button>
                                            </div>
                                            <flux:text class="text-sm whitespace-pre-wrap break-words">{{ $log['message'] }}</flux:text>

                                            @if($log['context'])
                                                <div>
                                                    <flux:heading size="sm">{{ __('Context') }}</flux:heading>
                                                    <pre class="text-xs bg-slate-800 text-slate-100 p-3 rounded overflow-x-auto mt-1">{{ json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif
                                            @if($log['stackTrace'])
                                                <div>
                                                    <flux:heading size="sm">{{ __('Stack Trace') }}</flux:heading>
                                                    <pre class="text-xs bg-slate-800 text-slate-100 p-3 rounded overflow-x-auto max-h-96 mt-1">{{ $log['stackTrace'] }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-slate-400" />
                                    <flux:heading size="lg" class="mt-4">{{ __('No log entries found') }}</flux:heading>
                                    <flux:text class="mt-2 text-slate-500">
                                        @if($search || $level)
                                            {{ __('Try adjusting your search or filter criteria') }}
                                        @elseif(empty($logFiles))
                                            {{ __('No log files available') }}
                                        @else
                                            {{ __('No logs available in the selected file') }}
                                        @endif
                                    </flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        @endif
    @endif

    <!-- Clear Logs Confirmation Modal -->
    <flux:modal wire:model="confirmingClear" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Clear Old Logs') }}</flux:heading>
                <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
                    {{ __('This will permanently delete log files older than the specified number of days.') }}
                </flux:text>
            </div>

            <div>
                <flux:input
                    type="number"
                    wire:model="clearDaysOld"
                    min="1"
                    max="365"
                    label="{{ __('Delete logs older than (days)') }}"
                />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('confirmingClear', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="clearOldLogs">
                    {{ __('Clear Logs') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
