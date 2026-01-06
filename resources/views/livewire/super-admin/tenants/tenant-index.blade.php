<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Tenants</flux:heading>
            <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
                Manage all tenant organizations
            </flux:text>
        </div>
        <flux:button variant="primary" :href="route('superadmin.tenants.create')" wire:navigate>
            <flux:icon.plus class="size-4 mr-2" />
            Create Tenant
        </flux:button>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search by name, ID, or email..."
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="status">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Tenants Table -->
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Tenant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Created
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($tenants as $tenant)
                    <tr wire:key="tenant-{{ $tenant->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                    <flux:icon.building-office class="size-5 text-slate-600 dark:text-slate-400" />
                                </div>
                                <div>
                                    <flux:text class="font-medium">{{ $tenant->name ?? 'Unnamed' }}</flux:text>
                                    <flux:text class="text-sm text-slate-500">{{ Str::limit($tenant->id, 20) }}</flux:text>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm">{{ $tenant->contact_email ?? '-' }}</flux:text>
                            <flux:text class="text-sm text-slate-500">{{ $tenant->contact_phone ?? '' }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge :color="$tenant->status?->color() ?? 'zinc'" size="sm">
                                {{ $tenant->status?->label() ?? 'Unknown' }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-slate-500">
                                {{ $tenant->created_at->format('M d, Y') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <flux:button variant="ghost" size="sm" :href="route('superadmin.tenants.show', $tenant)" wire:navigate>
                                View
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <flux:icon.building-office-2 class="mx-auto size-12 text-slate-400" />
                            <flux:heading size="lg" class="mt-4">No tenants found</flux:heading>
                            <flux:text class="mt-2 text-slate-500">
                                @if($search || $status)
                                    Try adjusting your search or filter criteria
                                @else
                                    Get started by creating your first tenant
                                @endif
                            </flux:text>
                            @if(!$search && !$status)
                                <flux:button variant="primary" :href="route('superadmin.tenants.create')" wire:navigate class="mt-4">
                                    Create Tenant
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($tenants->hasPages())
        <div class="mt-6">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
