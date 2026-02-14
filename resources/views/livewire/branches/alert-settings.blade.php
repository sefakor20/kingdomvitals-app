<section class="w-full">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/50">
                <flux:icon icon="bell-alert" class="size-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:heading size="xl" level="1">{{ __('AI Alert Settings') }}</flux:heading>
                <flux:subheading>{{ __('Configure alert thresholds and notifications for :branch', ['branch' => $branch->name]) }}</flux:subheading>
            </div>
        </div>

        <flux:button variant="ghost" :href="route('ai-insights.dashboard', $branch)" icon="arrow-left" wire:navigate>
            {{ __('Back to Insights') }}
        </flux:button>
    </div>

    {{-- Success notification --}}
    <div
        x-data="{ show: false }"
        x-on:settings-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
        x-cloak
        class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30"
    >
        <div class="flex items-center gap-2">
            <flux:icon icon="check-circle" class="size-5 text-green-600 dark:text-green-400" />
            <flux:text class="text-green-800 dark:text-green-200">{{ __('Alert settings saved successfully.') }}</flux:text>
        </div>
    </div>

    {{-- Alert Type Cards --}}
    <div class="space-y-4">
        @foreach($this->alertTypes as $alertType => $type)
            @php
                $setting = $settings[$alertType] ?? [];
                $isExpanded = $expanded[$alertType] ?? false;
                $hasThreshold = $type->defaultThreshold() !== null;
            @endphp

            <div class="rounded-xl border {{ $setting['is_enabled'] ? 'border-zinc-200 dark:border-zinc-700' : 'border-zinc-100 dark:border-zinc-800' }} bg-white dark:bg-zinc-900">
                {{-- Card Header --}}
                <div class="flex items-center justify-between p-4 {{ $isExpanded ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                    <div class="flex items-center gap-4">
                        {{-- Enable Toggle --}}
                        <flux:switch
                            wire:model.live="settings.{{ $alertType }}.is_enabled"
                            :label="null"
                        />

                        {{-- Alert Type Info --}}
                        <div class="flex items-center gap-3">
                            <div class="rounded-full p-2 {{ $setting['is_enabled'] ? 'bg-' . $type->color() . '-100 dark:bg-' . $type->color() . '-900/50' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <flux:icon :icon="$type->icon()" class="size-5 {{ $setting['is_enabled'] ? 'text-' . $type->color() . '-600 dark:text-' . $type->color() . '-400' : 'text-zinc-400' }}" />
                            </div>
                            <div>
                                <flux:text class="font-medium {{ !$setting['is_enabled'] ? 'text-zinc-400 dark:text-zinc-500' : '' }}">
                                    {{ $type->label() }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $type->description() }}
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    {{-- Expand Button --}}
                    <div class="flex items-center gap-3">
                        @if($setting['last_triggered_at'])
                            <flux:badge color="zinc" size="sm">
                                {{ __('Last: :time', ['time' => $setting['last_triggered_at']]) }}
                            </flux:badge>
                        @endif

                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="toggleExpanded('{{ $alertType }}')"
                        >
                            <flux:icon :icon="$isExpanded ? 'chevron-up' : 'chevron-down'" class="size-4" />
                        </flux:button>
                    </div>
                </div>

                {{-- Expanded Content --}}
                @if($isExpanded)
                    <div class="space-y-6 p-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            {{-- Threshold (if applicable) --}}
                            @if($hasThreshold)
                                <div>
                                    <flux:input
                                        wire:model="settings.{{ $alertType }}.threshold_value"
                                        type="number"
                                        min="0"
                                        max="100"
                                        :label="__('Threshold (%)')"
                                        :placeholder="__('Default: :default%', ['default' => $type->defaultThreshold()])"
                                    />
                                    <flux:text class="mt-1 text-xs text-zinc-500">
                                        {{ __('Alerts trigger when the score exceeds this value. Default: :default%', ['default' => $type->defaultThreshold()]) }}
                                    </flux:text>
                                </div>
                            @endif

                            {{-- Cooldown Period --}}
                            <div>
                                <flux:select
                                    wire:model="settings.{{ $alertType }}.cooldown_hours"
                                    :label="__('Cooldown Period')"
                                >
                                    @foreach($this->cooldownOptions as $option)
                                        <flux:select.option :value="$option['value']">
                                            {{ $option['label'] }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    {{ __('Minimum time between alerts for the same entity.') }}
                                </flux:text>
                            </div>
                        </div>

                        {{-- Notification Channels --}}
                        <div>
                            <flux:text class="mb-2 text-sm font-medium">{{ __('Notification Channels') }}</flux:text>
                            <div class="flex flex-wrap gap-3">
                                @foreach($this->notificationChannels as $channel => $label)
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleChannel('{{ $alertType }}', '{{ $channel }}')"
                                            @checked(in_array($channel, $setting['notification_channels'] ?? [], true))
                                            class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800"
                                        />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ __('Select where notifications should be delivered.') }}
                            </flux:text>
                        </div>

                        {{-- Recipient Roles --}}
                        <div>
                            <flux:text class="mb-2 text-sm font-medium">{{ __('Notify These Roles') }}</flux:text>
                            <div class="flex flex-wrap gap-3">
                                @foreach($this->recipientRoles as $role => $label)
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleRole('{{ $alertType }}', '{{ $role }}')"
                                            @checked(in_array($role, $setting['recipient_roles'] ?? [], true))
                                            class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800"
                                        />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-500">
                                {{ __('Select which user roles should receive these alerts.') }}
                            </flux:text>
                        </div>

                        {{-- Reset Button --}}
                        <div class="flex justify-end border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="resetToDefaults('{{ $alertType }}')"
                            >
                                <flux:icon icon="arrow-path" class="size-4" />
                                {{ __('Reset to Defaults') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Save Button --}}
    <div class="mt-6 flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">{{ __('Save All Settings') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
        </flux:button>
    </div>
</section>
