<div>
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" :href="route('superadmin.tenants.index')" wire:navigate>
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl">{{ $tenant->name ?? 'Unnamed Tenant' }}</flux:heading>
                <flux:text class="text-zinc-500">{{ $tenant->id }}</flux:text>
            </div>
            <flux:badge :color="$tenant->status?->color() ?? 'zinc'" size="lg">
                {{ $tenant->status?->label() ?? 'Unknown' }}
            </flux:badge>
            @unless($tenant->trashed())
                <flux:button variant="ghost" wire:click="openEditModal" icon="pencil">
                    Edit
                </flux:button>
            @endunless
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
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700 flex items-center justify-between">
                    <flux:heading size="lg">Domains</flux:heading>
                    @unless($tenant->trashed())
                        <flux:button wire:click="$set('showAddDomainModal', true)" variant="ghost" size="sm">
                            <flux:icon.plus class="size-4" />
                        </flux:button>
                    @endunless
                </div>
                <div class="p-6">
                    @if($tenant->domains->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach($tenant->domains as $domain)
                                <li class="flex items-center justify-between" wire:key="domain-{{ $domain->id }}">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.globe-alt class="size-4 text-zinc-400" />
                                        <flux:text>{{ $domain->domain }}</flux:text>
                                        @if($loop->first)
                                            <flux:badge color="green" size="sm">Primary</flux:badge>
                                        @endif
                                    </div>
                                    @if($tenant->domains->count() > 1 && !$tenant->trashed())
                                        <flux:button
                                            wire:click="confirmRemoveDomain('{{ $domain->id }}')"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <flux:icon.trash class="size-4 text-red-500" />
                                        </flux:button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="text-zinc-500">No domains configured</flux:text>
                    @endif
                </div>
            </div>

            <!-- Tenant Users -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">Users</flux:heading>
                </div>
                <div class="p-6">
                    @if($this->tenantUsers->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach($this->tenantUsers as $user)
                                <li class="flex items-center justify-between" wire:key="user-{{ $user->id }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                                            <flux:icon.user class="size-4 text-zinc-600 dark:text-zinc-400" />
                                        </div>
                                        <div>
                                            <flux:text class="font-medium">{{ $user->name }}</flux:text>
                                            <flux:text class="text-sm text-zinc-500">{{ $user->email }}</flux:text>
                                        </div>
                                    </div>
                                    @unless($tenant->trashed())
                                        <flux:button
                                            wire:click="resendInvitation('{{ $user->id }}')"
                                            wire:confirm="Are you sure you want to resend the invitation email to {{ $user->email }}?"
                                            variant="ghost"
                                            size="sm"
                                            icon="envelope"
                                        >
                                            Resend Invite
                                        </flux:button>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="text-zinc-500">No users found</flux:text>
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

                    @if($tenant->trashed())
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4">
                            <flux:text class="text-sm text-red-600 dark:text-red-400 font-medium">This tenant has been deleted</flux:text>
                            <flux:text class="text-xs text-red-500 mt-2">Deleted {{ $tenant->deleted_at->diffForHumans() }}</flux:text>
                        </div>
                        <flux:button wire:click="restore" variant="primary" class="w-full">
                            <flux:icon.arrow-path class="size-4 mr-2" />
                            Restore Tenant
                        </flux:button>
                    @elseif($tenant->isSuspended())
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

            <!-- Danger Zone -->
            @unless($tenant->trashed())
                <div class="rounded-xl border border-red-200 bg-white dark:border-red-900 dark:bg-zinc-800">
                    <div class="border-b border-red-200 px-6 py-4 dark:border-red-900">
                        <flux:heading size="lg" class="text-red-600">Danger Zone</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:text class="text-sm text-zinc-500 mb-4">
                            Deleting a tenant will remove their access immediately. This action can be reversed.
                        </flux:text>
                        <flux:button wire:click="$set('showDeleteModal', true)" variant="danger" icon="trash" class="w-full">
                            Delete Tenant
                        </flux:button>
                    </div>
                </div>
            @endunless

            <!-- Subscription Plan -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700 flex items-center justify-between">
                    <flux:heading size="lg">Subscription</flux:heading>
                    @unless($tenant->trashed())
                        <flux:button wire:click="$set('showSubscriptionModal', true)" variant="ghost" size="sm">
                            Change
                        </flux:button>
                    @endunless
                </div>
                <div class="p-6">
                    @if($tenant->subscriptionPlan)
                        <div class="space-y-3">
                            <flux:text class="font-medium">{{ $tenant->subscriptionPlan->name }}</flux:text>
                            @if($tenant->subscriptionPlan->description)
                                <flux:text class="text-sm text-zinc-500">{{ $tenant->subscriptionPlan->description }}</flux:text>
                            @endif
                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700">
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-zinc-500">Monthly</dt>
                                        <dd class="font-medium">${{ number_format($tenant->subscriptionPlan->price_monthly, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-zinc-500">Max Members</dt>
                                        <dd class="font-medium">{{ $tenant->subscriptionPlan->max_members ?? 'Unlimited' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-zinc-500">Max Branches</dt>
                                        <dd class="font-medium">{{ $tenant->subscriptionPlan->max_branches ?? 'Unlimited' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    @else
                        <flux:text class="text-zinc-500">No subscription plan assigned</flux:text>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">Quick Stats</flux:heading>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-zinc-500">Domains</dt>
                            <dd class="font-medium">{{ $tenant->domains->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Impersonation -->
            @if($tenant->isActive() && $tenant->domains->isNotEmpty() && !$tenant->trashed())
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="lg">Support Access</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:text class="text-sm text-zinc-500 mb-4">
                            Login as this tenant to provide support or troubleshoot issues.
                        </flux:text>
                        <flux:button wire:click="$set('showImpersonateModal', true)" variant="ghost" icon="user-circle" class="w-full">
                            Login as Tenant
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Suspend Modal -->
    <flux:modal wire:model="showSuspendModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Suspend Tenant</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
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

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Tenant</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Are you sure you want to delete {{ $tenant->name ?? 'this tenant' }}? They will lose access immediately. This action can be reversed by restoring the tenant.
                </flux:text>
            </div>

            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="size-5 text-amber-600 shrink-0" />
                    <flux:text class="text-sm text-amber-700 dark:text-amber-400">
                        Type <strong>DELETE</strong> to confirm this action.
                    </flux:text>
                </div>
            </div>

            <flux:input
                wire:model="deleteConfirmation"
                label="Confirmation"
                placeholder="Type DELETE to confirm"
            />

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="delete" variant="danger">
                    Delete Tenant
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Subscription Modal -->
    <flux:modal wire:model="showSubscriptionModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Change Subscription Plan</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Select a subscription plan for this tenant.
                </flux:text>
            </div>

            <flux:select wire:model="selectedPlanId" label="Subscription Plan">
                <option value="">No Plan</option>
                @foreach($subscriptionPlans as $plan)
                    <option value="{{ $plan->id }}">
                        {{ $plan->name }} - ${{ number_format($plan->price_monthly, 2) }}/mo
                    </option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showSubscriptionModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="updateSubscription" variant="primary">
                    Update Subscription
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Add Domain Modal -->
    <flux:modal wire:model="showAddDomainModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Domain</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Add a new domain for this tenant.
                </flux:text>
            </div>

            <flux:input
                wire:model="newDomain"
                label="Domain"
                placeholder="subdomain.kingdomvitals.com"
                description="Enter the full domain or subdomain"
            />

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showAddDomainModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="addDomain" variant="primary">
                    Add Domain
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Remove Domain Confirmation Modal -->
    <flux:modal wire:model="showRemoveDomainModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Domain</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Are you sure you want to remove this domain? Users will no longer be able to access the tenant via this domain.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showRemoveDomainModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="removeDomain" variant="danger">
                    Remove Domain
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Impersonate Modal -->
    <flux:modal wire:model="showImpersonateModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Impersonate Tenant</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    You are about to log in as this tenant. This action will be logged.
                </flux:text>
            </div>

            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="size-5 text-amber-600 shrink-0" />
                    <div class="text-sm text-amber-700 dark:text-amber-400">
                        <p class="font-medium">Important:</p>
                        <ul class="mt-1 list-disc pl-4 space-y-1">
                            <li>Session limited to 60 minutes</li>
                            <li>All actions will be logged</li>
                            <li>You will be logged in as the tenant owner</li>
                        </ul>
                    </div>
                </div>
            </div>

            <flux:textarea
                wire:model="impersonationReason"
                label="Reason for Impersonation"
                placeholder="Describe why you need to access this tenant's account (min 10 characters)..."
                rows="3"
                required
            />

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showImpersonateModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="impersonate" variant="primary">
                    Start Impersonation
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Edit Tenant Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Tenant</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Update the tenant's organization details.
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="editName"
                    label="Organization Name"
                    placeholder="Church of Example"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="editContactEmail"
                        label="Contact Email"
                        type="email"
                        placeholder="admin@example.com"
                    />

                    <flux:input
                        wire:model="editContactPhone"
                        label="Contact Phone"
                        placeholder="+1 234 567 8900"
                    />
                </div>

                <flux:textarea
                    wire:model="editAddress"
                    label="Address"
                    placeholder="123 Main Street, City, State"
                    rows="2"
                />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showEditModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="updateTenant" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Toast Notifications -->
    <x-toast on="tenant-suspended" type="success">
        {{ __('Tenant suspended successfully.') }}
    </x-toast>
    <x-toast on="tenant-reactivated" type="success">
        {{ __('Tenant reactivated successfully.') }}
    </x-toast>
    <x-toast on="tenant-status-updated" type="success">
        {{ __('Tenant status updated.') }}
    </x-toast>
    <x-toast on="tenant-restored" type="success">
        {{ __('Tenant restored successfully.') }}
    </x-toast>
    <x-toast on="subscription-updated" type="success">
        {{ __('Subscription updated successfully.') }}
    </x-toast>
    <x-toast on="domain-added" type="success">
        {{ __('Domain added successfully.') }}
    </x-toast>
    <x-toast on="domain-removed" type="success">
        {{ __('Domain removed successfully.') }}
    </x-toast>
    <x-toast on="tenant-updated" type="success">
        {{ __('Tenant updated successfully.') }}
    </x-toast>

    <!-- Session Flash Toast (for redirects from create) -->
    @if(session('success'))
        <x-toast on="page-loaded" type="success">
            {{ session('success') }}
        </x-toast>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Livewire.dispatch('page-loaded');
            });
        </script>
    @endif
</div>
