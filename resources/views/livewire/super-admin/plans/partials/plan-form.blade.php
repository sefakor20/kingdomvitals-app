<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <!-- Name -->
    <flux:field>
        <flux:label>{{ __('Plan Name') }}</flux:label>
        <flux:input wire:model.live="name" placeholder="{{ __('e.g., Professional') }}" />
        <flux:error name="name" />
    </flux:field>

    <!-- Slug -->
    <flux:field>
        <flux:label>{{ __('Slug') }}</flux:label>
        <flux:input wire:model="slug" placeholder="{{ __('e.g., professional') }}" />
        <flux:error name="slug" />
    </flux:field>
</div>

<!-- Description -->
<flux:field>
    <flux:label>{{ __('Description') }}</flux:label>
    <flux:textarea wire:model="description" rows="2" placeholder="{{ __('Brief description of the plan...') }}" />
    <flux:error name="description" />
</flux:field>

<!-- Pricing (GHS) -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <flux:field>
        <flux:label>{{ __('Monthly Price (GHS)') }}</flux:label>
        <flux:input wire:model="priceMonthly" type="number" step="0.01" min="0" />
        <flux:error name="priceMonthly" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Annual Price (GHS)') }}</flux:label>
        <flux:input wire:model="priceAnnual" type="number" step="0.01" min="0" />
        <flux:error name="priceAnnual" />
    </flux:field>
</div>

<!-- Pricing (USD) -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <flux:field>
        <flux:label>{{ __('Monthly Price (USD)') }}</flux:label>
        <flux:input wire:model="priceMonthlyUsd" type="number" step="0.01" min="0" placeholder="{{ __('Optional') }}" />
        <flux:error name="priceMonthlyUsd" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Annual Price (USD)') }}</flux:label>
        <flux:input wire:model="priceAnnualUsd" type="number" step="0.01" min="0" placeholder="{{ __('Optional') }}" />
        <flux:error name="priceAnnualUsd" />
    </flux:field>
</div>

<!-- Resource Limits -->
<div>
    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">{{ __('Resource Limits') }}</flux:heading>
    <flux:text class="mb-3 text-xs text-zinc-500">{{ __('Leave empty for unlimited access.') }}</flux:text>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('Max Members') }}</flux:label>
            <flux:input wire:model="maxMembers" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxMembers" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Max Branches') }}</flux:label>
            <flux:input wire:model="maxBranches" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxBranches" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Max Households') }}</flux:label>
            <flux:input wire:model="maxHouseholds" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxHouseholds" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Max Clusters') }}</flux:label>
            <flux:input wire:model="maxClusters" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxClusters" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Max Visitors') }}</flux:label>
            <flux:input wire:model="maxVisitors" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxVisitors" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Max Equipment') }}</flux:label>
            <flux:input wire:model="maxEquipment" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="maxEquipment" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Storage Quota (GB)') }}</flux:label>
            <flux:input wire:model="storageQuotaGb" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="storageQuotaGb" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('SMS Credits/Month') }}</flux:label>
            <flux:input wire:model="smsCreditsMonthly" type="number" min="0" placeholder="{{ __('Unlimited') }}" />
            <flux:error name="smsCreditsMonthly" />
        </flux:field>
    </div>
</div>

<!-- Enabled Modules -->
<div>
    <flux:heading size="sm" class="mb-1 text-zinc-700 dark:text-zinc-300">{{ __('Enabled Modules') }}</flux:heading>
    <flux:text class="mb-3 text-xs text-zinc-500">{{ __('Leave all unchecked to enable every module. Check specific modules to restrict access.') }}</flux:text>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
        @foreach($planModules as $module)
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800
                {{ in_array($module->value, $enabledModules) ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' : '' }}">
                <input
                    type="checkbox"
                    wire:model="enabledModules"
                    value="{{ $module->value }}"
                    class="rounded border-zinc-300 text-blue-600 dark:border-zinc-600"
                />
                <span>{{ $module->label() }}</span>
            </label>
        @endforeach
    </div>
    <flux:error name="enabledModules" />
</div>

<!-- Support & Display -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <flux:field>
        <flux:label>{{ __('Support Level') }}</flux:label>
        <flux:select wire:model="supportLevel">
            @foreach($supportLevels as $level)
                <flux:select.option value="{{ $level->value }}">
                    {{ $level->label() }} ({{ $level->responseTime() }})
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="supportLevel" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Display Order') }}</flux:label>
        <flux:input wire:model="displayOrder" type="number" min="0" />
        <flux:description>{{ __('Lower numbers appear first') }}</flux:description>
        <flux:error name="displayOrder" />
    </flux:field>
</div>

<!-- Features -->
<flux:field>
    <flux:label>{{ __('Features') }}</flux:label>
    <flux:textarea wire:model="featuresInput" rows="3" placeholder="{{ __('Feature 1, Feature 2, Feature 3...') }}" />
    <flux:description>{{ __('Comma-separated list of features') }}</flux:description>
    <flux:error name="featuresInput" />
</flux:field>

<!-- Toggles -->
<div class="flex flex-wrap gap-6">
    <flux:field>
        <flux:switch wire:model="isActive" label="{{ __('Active') }}" description="{{ __('Available for tenant subscription') }}" />
    </flux:field>

    <flux:field>
        <flux:switch wire:model="isDefault" label="{{ __('Default Plan') }}" description="{{ __('Assigned to new tenants') }}" />
    </flux:field>
</div>
