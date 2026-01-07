<div>
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Subscription Plans') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                {{ __('Manage subscription plans and pricing for tenants.') }}
            </flux:text>
        </div>

        @if($canManage)
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
                {{ __('Add Plan') }}
            </flux:button>
        @endif
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($plans as $plan)
            <div wire:key="plan-{{ $plan->id }}" class="relative rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <!-- Action Menu -->
                @if($canManage)
                    <div class="absolute right-4 top-4">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="openEditModal('{{ $plan->id }}')">
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                @if(!$plan->is_default)
                                    <flux:menu.item icon="star" wire:click="setAsDefault('{{ $plan->id }}')">
                                        {{ __('Set as Default') }}
                                    </flux:menu.item>
                                @endif
                                <flux:menu.item icon="{{ $plan->is_active ? 'eye-slash' : 'eye' }}" wire:click="toggleActive('{{ $plan->id }}')">
                                    {{ $plan->is_active ? __('Deactivate') : __('Activate') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $plan->id }}')">
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endif

                <div class="p-6">
                    <!-- Plan Name & Badges -->
                    <div class="mb-4 flex items-start gap-2 pr-8">
                        <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                        <div class="flex flex-wrap gap-1">
                            @if($plan->is_default)
                                <flux:badge color="indigo" size="sm">{{ __('Default') }}</flux:badge>
                            @endif
                            @if(!$plan->is_active)
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-zinc-900 dark:text-white">{{ Number::currency($plan->price_monthly, in: 'GHS') }}</span>
                        <span class="text-zinc-500">/{{ __('month') }}</span>
                        @if($plan->price_annual > 0)
                            <div class="mt-1 text-sm text-zinc-500">
                                {{ Number::currency($plan->price_annual, in: 'GHS') }}/{{ __('year') }}
                                @if($plan->getAnnualSavingsPercent() > 0)
                                    <span class="text-green-600">({{ __('Save') }} {{ number_format($plan->getAnnualSavingsPercent(), 0) }}%)</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Description -->
                    @if($plan->description)
                        <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                            {{ $plan->description }}
                        </flux:text>
                    @endif

                    <!-- Limits -->
                    <div class="mb-4 space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.users class="size-4 {{ $plan->hasUnlimitedMembers() ? 'text-green-500' : 'text-zinc-400' }}" />
                            <span>{{ $plan->hasUnlimitedMembers() ? __('Unlimited members') : number_format($plan->max_members) . ' ' . __('members') }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.building-office class="size-4 {{ $plan->hasUnlimitedBranches() ? 'text-green-500' : 'text-zinc-400' }}" />
                            <span>{{ $plan->hasUnlimitedBranches() ? __('Unlimited branches') : number_format($plan->max_branches) . ' ' . __('branches') }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.server class="size-4 text-zinc-400" />
                            <span>{{ $plan->storage_quota_gb }} GB {{ __('storage') }}</span>
                        </div>

                        @if($plan->sms_credits_monthly)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.chat-bubble-left class="size-4 text-zinc-400" />
                                <span>{{ number_format($plan->sms_credits_monthly) }} {{ __('SMS/month') }}</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.lifebuoy class="size-4 text-zinc-400" />
                            <span>{{ $plan->support_level?->label() ?? 'Community' }} {{ __('support') }}</span>
                        </div>
                    </div>

                    <!-- Features -->
                    @if($plan->features && count($plan->features) > 0)
                        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:text class="mb-2 text-sm font-medium">{{ __('Features:') }}</flux:text>
                            <ul class="space-y-1">
                                @foreach($plan->features as $feature)
                                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <flux:icon.check class="size-4 text-green-500" />
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.credit-card class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No plans configured') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    {{ __('Subscription plans will appear here once configured.') }}
                </flux:text>
                @if($canManage)
                    <flux:button variant="primary" class="mt-4" wire:click="$set('showCreateModal', true)">
                        {{ __('Create First Plan') }}
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Create Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Plan') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Add a new subscription plan for tenants.') }}
                </flux:text>
            </div>

            <form wire:submit="createPlan" class="space-y-4">
                @include('livewire.super-admin.plans.partials.plan-form', ['supportLevels' => $supportLevels])

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Plan') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Plan') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Update subscription plan details.') }}
                </flux:text>
            </div>

            <form wire:submit="updatePlan" class="space-y-4">
                @include('livewire.super-admin.plans.partials.plan-form', ['supportLevels' => $supportLevels])

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showEditModal', false)" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="size-8 text-red-600 dark:text-red-400" />
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ __('Delete Plan') }}</flux:heading>
                    @if($deleteSubscriberCount > 0)
                        <flux:text class="text-red-600">
                            {{ __('This plan has :count active subscriber(s). You must reassign them before deleting.', ['count' => $deleteSubscriberCount]) }}
                        </flux:text>
                    @else
                        <flux:text class="text-zinc-500">
                            {{ __('Are you sure you want to delete this subscription plan? This action cannot be undone.') }}
                        </flux:text>
                    @endif
                </div>
            </div>

            @error('delete')
                <flux:callout variant="danger" icon="x-circle" :heading="$message" />
            @enderror

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" wire:click="$set('showDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" class="flex-1" wire:click="deletePlan" :disabled="$deleteSubscriberCount > 0">
                    {{ __('Delete Plan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Toast Notifications -->
    <x-toast on="plan-created" type="success">
        {{ __('Subscription plan created successfully.') }}
    </x-toast>
    <x-toast on="plan-updated" type="success">
        {{ __('Subscription plan updated successfully.') }}
    </x-toast>
    <x-toast on="plan-deleted" type="success">
        {{ __('Subscription plan deleted successfully.') }}
    </x-toast>
    <x-toast on="plan-status-changed" type="success">
        {{ __('Plan status updated successfully.') }}
    </x-toast>
    <x-toast on="plan-default-changed" type="success">
        {{ __('Default plan updated successfully.') }}
    </x-toast>
</div>
