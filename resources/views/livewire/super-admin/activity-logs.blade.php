<div>
    <div class="mb-8">
        <flux:heading size="xl">Activity Logs</flux:heading>
        <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
            View all super admin activity on the platform
        </flux:text>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search by description or admin name..."
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="action">
                <option value="">All Actions</option>
                @foreach($actions as $actionOption)
                    <option value="{{ $actionOption }}">{{ str_replace('_', ' ', ucfirst($actionOption)) }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Admin
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Action
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Tenant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        IP Address
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Time
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                                    <flux:icon.user class="size-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <flux:text class="font-medium">{{ $log->superAdmin?->name ?? 'System' }}</flux:text>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="zinc" size="sm">
                                {{ str_replace('_', ' ', $log->action) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">
                            <flux:text class="text-sm max-w-xs truncate">{{ $log->description ?? '-' }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->tenant)
                                <flux:button variant="ghost" size="xs" :href="route('superadmin.tenants.show', $log->tenant)" wire:navigate>
                                    {{ $log->tenant->name ?? Str::limit($log->tenant->id, 15) }}
                                </flux:button>
                            @else
                                <flux:text class="text-sm text-slate-400">-</flux:text>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-slate-500">{{ $log->ip_address ?? '-' }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-slate-500" title="{{ $log->created_at->format('M d, Y H:i:s') }}">
                                {{ $log->created_at->diffForHumans() }}
                            </flux:text>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <flux:icon.clipboard-document-list class="mx-auto size-12 text-slate-400" />
                            <flux:heading size="lg" class="mt-4">No activity logs</flux:heading>
                            <flux:text class="mt-2 text-slate-500">
                                @if($search || $action)
                                    Try adjusting your search or filter criteria
                                @else
                                    Activity will appear here as actions are performed
                                @endif
                            </flux:text>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($logs->hasPages())
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
</div>
