<div>
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Household') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('View your household and family members.') }}
        </flux:text>
    </div>

    @if ($this->household)
        {{-- Household Info Card --}}
        <flux:card class="mb-6">
            <div class="flex items-start gap-4">
                <div class="flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="home" class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ $this->household->name }}</flux:heading>
                    @if ($this->household->address)
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                            {{ $this->household->address }}
                        </flux:text>
                    @endif
                    <div class="mt-2 flex items-center gap-4">
                        <flux:text class="text-sm text-zinc-500">
                            <span class="font-medium">{{ $this->familyMembers->count() }}</span> {{ __('members') }}
                        </flux:text>
                        @if ($this->household->head)
                            <flux:text class="text-sm text-zinc-500">
                                {{ __('Head:') }} <span class="font-medium">{{ $this->household->head->fullName() }}</span>
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Family Members --}}
        <flux:card>
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Family Members') }}</flux:heading>
            </div>

            @if ($this->familyMembers->isEmpty())
                <div class="p-12 text-center">
                    <flux:icon name="users" class="mx-auto mb-4 size-12 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('No members in this household.') }}
                    </flux:text>
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->familyMembers as $familyMember)
                        <div wire:key="member-{{ $familyMember->id }}" class="flex items-center gap-4 px-6 py-4">
                            @if ($familyMember->photo_url)
                                <img
                                    src="{{ $familyMember->photo_url }}"
                                    alt="{{ $familyMember->fullName() }}"
                                    class="size-12 rounded-full object-cover"
                                />
                            @else
                                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                    <flux:icon name="user" class="size-6 text-zinc-500 dark:text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-medium text-zinc-900 dark:text-white">
                                        {{ $familyMember->fullName() }}
                                    </flux:text>
                                    @if ($familyMember->id === $this->member->id)
                                        <flux:badge color="amber" size="sm">{{ __('You') }}</flux:badge>
                                    @endif
                                    @if ($familyMember->household_role)
                                        <flux:badge
                                            :color="match($familyMember->household_role) {
                                                \App\Enums\HouseholdRole::Head => 'blue',
                                                \App\Enums\HouseholdRole::Spouse => 'purple',
                                                \App\Enums\HouseholdRole::Child => 'green',
                                                default => 'zinc',
                                            }"
                                            size="sm"
                                        >
                                            {{ __(ucfirst($familyMember->household_role->value)) }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($familyMember->date_of_birth)
                                        {{ $familyMember->date_of_birth->age }} {{ __('years old') }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    @else
        {{-- No Household State --}}
        <flux:card>
            <div class="p-12 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="home" class="size-8 text-zinc-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ __('No Household Assigned') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('You are not currently part of a household. Please contact your church administrator to be added to a household.') }}
                </flux:text>
            </div>
        </flux:card>
    @endif
</div>
