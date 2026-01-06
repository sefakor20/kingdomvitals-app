<div>
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" :href="route('superadmin.tenants.index')" wire:navigate>
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl">{{ $tenant->name ?? 'Unnamed Tenant' }}</flux:heading>
                <flux:text class="text-slate-500">{{ $tenant->id }}</flux:text>
            </div>
            <flux:badge :color="$tenant->status?->color() ?? 'zinc'" size="lg">
                {{ $tenant->status?->label() ?? 'Unknown' }}
            </flux:badge>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tenant Details -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <flux:heading size="lg">Tenant Details</flux:heading>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Name</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $tenant->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Contact Email</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $tenant->contact_email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Contact Phone</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $tenant->contact_phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Address</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $tenant->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Created</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $tenant->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Trial Ends</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">
                                @if($tenant->trial_ends_at)
                                    {{ $tenant->trial_ends_at->format('M d, Y') }}
                                    @if($tenant->trialDaysRemaining() > 0)
                                        <span class="text-amber-600">({{ $tenant->trialDaysRemaining() }} days left)</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Domains -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <flux:heading size="lg">Domains</flux:heading>
                </div>
                <div class="p-6">
                    @if($tenant->domains->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($tenant->domains as $domain)
                                <li class="flex items-center gap-2">
                                    <flux:icon.globe-alt class="size-4 text-slate-400" />
                                    <flux:text>{{ $domain->domain }}</flux:text>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="text-slate-500">No domains configured</flux:text>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <flux:heading size="lg">Recent Activity</flux:heading>
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
                            <flux:text class="text-slate-500">No activity recorded for this tenant</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Status Management -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <flux:heading size="lg">Status</flux:heading>
                </div>
                <div class="p-6 space-y-4">
                    <flux:select wire:change="updateStatus($event.target.value)">
                        @foreach($statuses as $statusOption)
                            <option
                                value="{{ $statusOption->value }}"
                                @selected($tenant->status === $statusOption)
                            >
                                {{ $statusOption->label() }}
                            </option>
                        @endforeach
                    </flux:select>

                    @if($tenant->isSuspended())
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4">
                            <flux:text class="text-sm text-red-600 dark:text-red-400 font-medium">Suspension Reason:</flux:text>
                            <flux:text class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $tenant->suspension_reason ?? 'No reason provided' }}</flux:text>
                            @if($tenant->suspended_at)
                                <flux:text class="text-xs text-red-500 mt-2">Suspended {{ $tenant->suspended_at->diffForHumans() }}</flux:text>
                            @endif
                        </div>
                        <flux:button wire:click="reactivate" variant="primary" class="w-full">
                            Reactivate Tenant
                        </flux:button>
                    @else
                        <flux:button wire:click="$set('showSuspendModal', true)" variant="danger" class="w-full">
                            Suspend Tenant
                        </flux:button>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <flux:heading size="lg">Quick Stats</flux:heading>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Domains</dt>
                            <dd class="font-medium">{{ $tenant->domains->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <flux:modal wire:model="showSuspendModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Suspend Tenant</flux:heading>
                <flux:text class="mt-2 text-slate-500">
                    Are you sure you want to suspend {{ $tenant->name ?? 'this tenant' }}? They will lose access to their account until reactivated.
                </flux:text>
            </div>

            <flux:textarea
                wire:model="suspensionReason"
                label="Suspension Reason"
                placeholder="Provide a reason for the suspension..."
                rows="3"
            />

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showSuspendModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="suspend" variant="danger">
                    Suspend Tenant
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
