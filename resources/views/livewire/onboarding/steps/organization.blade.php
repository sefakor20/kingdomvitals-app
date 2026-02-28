<div class="space-y-6">
    <div class="text-center">
        <span class="label-mono text-emerald-600 dark:text-emerald-400">Step 1 of 5</span>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
            <span class="text-gradient-emerald">Set Up Your Organization</span>
        </h1>
        <p class="mt-2 text-secondary">
            Let's start by setting up your main branch. This will be the primary location for your church.
        </p>
    </div>

    <form wire:submit="completeOrganizationStep" class="space-y-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <flux:field>
                    <flux:label>Branch Name *</flux:label>
                    <flux:input
                        wire:model="branchName"
                        placeholder="e.g., Main Campus, Downtown Branch"
                    />
                    <flux:error name="branchName" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Timezone *</flux:label>
                <flux:select wire:model="timezone">
                    @foreach($this->timezones as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="timezone" />
            </flux:field>

            <flux:field>
                <flux:label>Country</flux:label>
                <flux:input wire:model="country" placeholder="e.g., Ghana" />
                <flux:error name="country" />
            </flux:field>

            <div class="sm:col-span-2">
                <flux:field>
                    <flux:label>Street Address</flux:label>
                    <flux:textarea
                        wire:model="address"
                        placeholder="Enter your branch address"
                        rows="2"
                    />
                    <flux:error name="address" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>City</flux:label>
                <flux:input wire:model="city" placeholder="e.g., Accra" />
                <flux:error name="city" />
            </flux:field>

            <flux:field>
                <flux:label>State / Region</flux:label>
                <flux:input wire:model="state" placeholder="e.g., Greater Accra" />
                <flux:error name="state" />
            </flux:field>

            <flux:field>
                <flux:label>Postal Code</flux:label>
                <flux:input wire:model="zip" placeholder="e.g., GA-000" />
                <flux:error name="zip" />
            </flux:field>

            <flux:field>
                <flux:label>Phone Number</flux:label>
                <flux:input wire:model="phone" type="tel" placeholder="e.g., +233 20 123 4567" />
                <flux:error name="phone" />
            </flux:field>

            <div class="sm:col-span-2">
                <flux:field>
                    <flux:label>Branch Email</flux:label>
                    <flux:input wire:model="branchEmail" type="email" placeholder="e.g., info@yourchurch.org" />
                    <flux:error name="branchEmail" />
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="btn-neon rounded-full px-8 py-3 text-sm font-semibold">
                Continue
                <flux:icon name="arrow-right" variant="mini" class="ml-2 inline size-4" />
            </button>
        </div>
    </form>
</div>
