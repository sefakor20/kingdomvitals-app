<div>
    <div class="mb-8">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
            Platform overview and statistics
        </flux:text>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Tenants -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                    <flux:icon.building-office-2 class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">Total Tenants</flux:text>
                    <flux:heading size="xl">{{ $totalTenants }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                    <flux:icon.check-circle class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">Active</flux:text>
                    <flux:heading size="xl">{{ $activeTenants }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Trial Tenants -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                    <flux:icon.clock class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">In Trial</flux:text>
                    <flux:heading size="xl">{{ $trialTenants }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Suspended Tenants -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900">
                    <flux:icon.x-circle class="size-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">Suspended</flux:text>
                    <flux:heading size="xl">{{ $suspendedTenants }}</flux:heading>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Tenants -->
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Recent Tenants</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('superadmin.tenants.index')" wire:navigate>
                        View all
                    </flux:button>
                </div>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($recentTenants as $tenant)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                <flux:icon.building-office class="size-5 text-slate-600 dark:text-slate-400" />
                            </div>
                            <div>
                                <flux:text class="font-medium">{{ $tenant->name ?? $tenant->id }}</flux:text>
                                <flux:text class="text-sm text-slate-500">
                                    Created {{ $tenant->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                        <flux:badge :color="$tenant->status?->color() ?? 'zinc'" size="sm">
                            {{ $tenant->status?->label() ?? 'Unknown' }}
                        </flux:badge>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <flux:text class="text-slate-500">No tenants yet</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Recent Activity</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('superadmin.activity-logs')" wire:navigate>
                        View all
                    </flux:button>
                </div>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($recentActivity as $activity)
                    <div class="px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                <flux:icon.user class="size-4 text-slate-600 dark:text-slate-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium">{{ $activity->superAdmin?->name ?? 'System' }}</flux:text>
                                <flux:text class="text-sm text-slate-500">{{ $activity->description ?? $activity->action }}</flux:text>
                                <flux:text class="text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</flux:text>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <flux:text class="text-slate-500">No activity yet</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
