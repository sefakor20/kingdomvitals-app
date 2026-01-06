<div>
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('superadmin.tenants.index')" wire:navigate>
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div>
                <flux:heading size="xl">Create Tenant</flux:heading>
                <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
                    Set up a new organization on the platform
                </flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="max-w-2xl">
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                <flux:heading size="lg">Tenant Information</flux:heading>
            </div>
            <div class="p-6 space-y-6">
                <flux:input
                    wire:model="name"
                    label="Organization Name"
                    placeholder="Enter organization name"
                    required
                />

                <flux:input
                    wire:model="domain"
                    label="Domain"
                    placeholder="organization.kingdomvitals.com"
                    description="The subdomain for accessing this tenant's application"
                    required
                />

                <flux:input
                    wire:model="contact_email"
                    type="email"
                    label="Contact Email"
                    placeholder="contact@organization.com"
                />

                <flux:input
                    wire:model="contact_phone"
                    type="tel"
                    label="Contact Phone"
                    placeholder="+1 (555) 000-0000"
                />

                <flux:textarea
                    wire:model="address"
                    label="Address"
                    placeholder="Enter organization address"
                    rows="3"
                />

                <flux:input
                    wire:model="trial_days"
                    type="number"
                    label="Trial Period (days)"
                    min="0"
                    max="365"
                    description="Number of days for the trial period. Set to 0 for no trial."
                />
            </div>
            <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700 flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('superadmin.tenants.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Create Tenant
                </flux:button>
            </div>
        </div>
    </form>
</div>
