<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('superadmin.billing.invoices') }}" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Create Invoice') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('Generate a new invoice for a tenant') }}
                </flux:text>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tenant Selection --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Select Tenant') }}</flux:heading>

                <flux:field>
                    <flux:label>{{ __('Tenant') }}</flux:label>
                    <flux:select wire:model.live="tenantId">
                        <option value="">{{ __('Select a tenant...') }}</option>
                        @foreach($this->tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="tenantId" />
                </flux:field>

                @if($this->selectedTenant)
                    <div class="mt-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-zinc-500">{{ __('Subscription Plan') }}</dt>
                                <dd class="text-sm font-medium">{{ $this->selectedTenant->subscriptionPlan?->name ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-zinc-500">{{ __('Monthly Price') }}</dt>
                                <dd class="text-sm font-medium">GHS {{ number_format((float) ($this->selectedTenant->subscriptionPlan?->price_monthly ?? 0), 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-zinc-500">{{ __('Annual Price') }}</dt>
                                <dd class="text-sm font-medium">GHS {{ number_format((float) ($this->selectedTenant->subscriptionPlan?->price_annual ?? 0), 2) }}</dd>
                            </div>
                            @if($this->selectedTenant->contact_email)
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-500">{{ __('Contact Email') }}</dt>
                                    <dd class="text-sm">{{ $this->selectedTenant->contact_email }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Invoice Details --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Invoice Details') }}</flux:heading>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Billing Cycle') }}</flux:label>
                        <flux:select wire:model.live="billingCycle">
                            <option value="monthly">{{ __('Monthly') }}</option>
                            <option value="annual">{{ __('Annual') }}</option>
                        </flux:select>
                        <flux:error name="billingCycle" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Due Date') }}</flux:label>
                        <flux:input type="date" wire:model="dueDate" />
                        <flux:error name="dueDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Notes') }} ({{ __('Optional') }})</flux:label>
                        <flux:textarea wire:model="notes" rows="3" placeholder="{{ __('Add any notes or comments...') }}" />
                        <flux:error name="notes" />
                    </flux:field>
                </div>
            </div>

            {{-- Custom Line Items --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Line Items') }}</flux:heading>
                    <flux:switch wire:model.live="useCustomItems" label="{{ __('Use custom items') }}" />
                </div>

                @if($useCustomItems)
                    <div class="space-y-4">
                        @foreach($customItems as $index => $item)
                            <div wire:key="item-{{ $index }}" class="flex items-start gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex-1 space-y-3">
                                    <flux:field>
                                        <flux:label>{{ __('Description') }}</flux:label>
                                        <flux:input wire:model="customItems.{{ $index }}.description" placeholder="{{ __('Enter description...') }}" />
                                        <flux:error name="customItems.{{ $index }}.description" />
                                    </flux:field>

                                    <div class="grid grid-cols-2 gap-4">
                                        <flux:field>
                                            <flux:label>{{ __('Quantity') }}</flux:label>
                                            <flux:input type="number" min="1" wire:model.live="customItems.{{ $index }}.quantity" />
                                            <flux:error name="customItems.{{ $index }}.quantity" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>{{ __('Unit Price (GHS)') }}</flux:label>
                                            <flux:input type="number" step="0.01" min="0" wire:model.live="customItems.{{ $index }}.unit_price" />
                                            <flux:error name="customItems.{{ $index }}.unit_price" />
                                        </flux:field>
                                    </div>
                                </div>

                                <flux:button variant="ghost" icon="trash" wire:click="removeCustomItem({{ $index }})" class="text-red-500 hover:text-red-700" />
                            </div>
                        @endforeach

                        <flux:button variant="ghost" icon="plus" wire:click="addCustomItem">
                            {{ __('Add Line Item') }}
                        </flux:button>

                        @if(empty($customItems))
                            <div class="py-8 text-center text-zinc-500">
                                <flux:icon.document-text class="mx-auto size-8 text-zinc-300" />
                                <flux:text class="mt-2">{{ __('No line items added yet') }}</flux:text>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('The invoice will be generated automatically based on the tenant\'s subscription plan and selected billing cycle.') }}
                        </flux:text>
                    </div>
                @endif
            </div>
        </div>

        {{-- Summary Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Summary') }}</flux:heading>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Tenant') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->selectedTenant?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Billing Cycle') }}</dt>
                        <dd class="text-sm font-medium">{{ ucfirst($billingCycle) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Due Date') }}</dt>
                        <dd class="text-sm font-medium">{{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('M d, Y') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Type') }}</dt>
                        <dd class="text-sm font-medium">{{ $useCustomItems ? __('Custom') : __('Standard') }}</dd>
                    </div>
                </dl>

                <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="flex justify-between">
                        <dt class="text-lg font-semibold">{{ __('Estimated Total') }}</dt>
                        <dd class="text-lg font-bold text-indigo-600 dark:text-indigo-400">GHS {{ number_format($this->estimatedAmount, 2) }}</dd>
                    </div>
                </div>

                <div class="mt-6">
                    <flux:button variant="primary" class="w-full" wire:click="createInvoice" icon="document-plus">
                        {{ __('Create Invoice') }}
                    </flux:button>
                </div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <div class="flex gap-3">
                    <flux:icon.information-circle class="size-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                    <div>
                        <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                            {{ __('The invoice will be created in Draft status. You can review it before sending to the tenant.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
