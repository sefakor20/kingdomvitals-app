<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __("Children's Ministry Check-In") }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ $service->name }} - {{ $branch->name }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:input
                type="date"
                wire:model.live="selectedDate"
                class="w-40"
            />
            <flux:button
                href="{{ route('attendance.live-check-in', ['branch' => $branch, 'service' => $service]) }}"
                variant="ghost"
            >
                {{ __('Regular Check-In') }}
            </flux:button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <button
            wire:click="$set('activeTab', 'checkin')"
            class="px-4 py-2 text-sm font-medium transition {{ $activeTab === 'checkin' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
        >
            {{ __('Check In') }}
        </button>
        <button
            wire:click="$set('activeTab', 'checkout')"
            class="px-4 py-2 text-sm font-medium transition {{ $activeTab === 'checkout' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
        >
            {{ __('Check Out') }}
        </button>
        <button
            wire:click="$set('activeTab', 'active')"
            class="px-4 py-2 text-sm font-medium transition {{ $activeTab === 'active' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
        >
            {{ __('Active Children') }}
            @if ($this->checkedInChildren->count() > 0)
                <span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                    {{ $this->checkedInChildren->count() }}
                </span>
            @endif
        </button>
    </div>

    {{-- Check In Tab --}}
    @if ($activeTab === 'checkin')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Search --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Search Children') }}</flux:heading>

                <flux:input
                    wire:model.live.debounce.200ms="searchQuery"
                    placeholder="{{ __('Search by name...') }}"
                    class="mb-4"
                >
                    <x-slot:iconLeading>
                        <flux:icon name="magnifying-glass" class="h-5 w-5 text-zinc-400" />
                    </x-slot:iconLeading>
                </flux:input>

                @if ($this->searchResults->isEmpty() && strlen($searchQuery) >= 2)
                    <div class="py-8 text-center">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ __('No children found matching your search.') }}
                        </flux:text>
                    </div>
                @elseif ($this->searchResults->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($this->searchResults as $child)
                            <div
                                wire:key="child-{{ $child['id'] }}"
                                class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-600"
                            >
                                <div class="flex items-center gap-3">
                                    @if ($child['photo_url'])
                                        <img
                                            src="{{ $child['photo_url'] }}"
                                            alt="{{ $child['name'] }}"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                            <flux:icon name="user" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                        </div>
                                    @endif
                                    <div>
                                        <flux:text class="font-medium text-zinc-900 dark:text-white">
                                            {{ $child['name'] }}
                                        </flux:text>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            @if ($child['age'])
                                                {{ __(':age years old', ['age' => $child['age']]) }}
                                            @endif
                                            @if ($child['household_name'])
                                                {{ $child['age'] ? ' - ' : '' }}{{ $child['household_name'] }}
                                            @endif
                                        </flux:text>
                                    </div>
                                </div>
                                @if ($child['already_checked_in'])
                                    <flux:badge color="green" size="sm">
                                        {{ __('Checked In') }}
                                    </flux:badge>
                                @else
                                    <flux:button
                                        wire:click="openCheckInModal('{{ $child['id'] }}')"
                                        size="sm"
                                        variant="primary"
                                    >
                                        {{ __('Check In') }}
                                    </flux:button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <flux:icon name="user-group" class="mx-auto mb-2 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ __('Start typing to search for children') }}
                        </flux:text>
                    </div>
                @endif
            </div>

            {{-- Quick Stats --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4">{{ __('Today\'s Summary') }}</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Checked In') }}</flux:text>
                            <div class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ $this->checkedInChildren->count() }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <flux:text class="text-sm text-green-600 dark:text-green-400">{{ __('Checked Out') }}</flux:text>
                            <div class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">
                                {{ $this->checkedOutChildren->count() }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4">{{ __('Recent Check-Ins') }}</flux:heading>
                    @if ($this->checkedInChildren->isEmpty())
                        <flux:text class="text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No children checked in yet') }}
                        </flux:text>
                    @else
                        <div class="space-y-2">
                            @foreach ($this->checkedInChildren->take(5) as $record)
                                <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 dark:bg-zinc-700/50">
                                    <flux:text class="font-medium text-zinc-900 dark:text-white">
                                        {{ $record->child?->fullName() }}
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $record->attendance?->check_in_time ? substr($record->attendance->check_in_time, 0, 5) : '' }}
                                    </flux:text>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Check Out Tab --}}
    @if ($activeTab === 'checkout')
        <div class="mx-auto max-w-md">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4 text-center">{{ __('Enter Security Code') }}</flux:heading>
                <flux:text class="mb-6 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('Enter the 6-digit security code provided at check-in.') }}
                </flux:text>

                <div class="mb-4">
                    <flux:input
                        wire:model="checkoutCode"
                        type="text"
                        maxlength="6"
                        placeholder="000000"
                        class="text-center text-2xl tracking-widest"
                        pattern="[0-9]*"
                        inputmode="numeric"
                    />
                </div>

                @if ($checkoutError)
                    <div class="mb-4 rounded-lg bg-red-50 p-3 text-center dark:bg-red-900/20">
                        <flux:text class="text-red-600 dark:text-red-400">{{ $checkoutError }}</flux:text>
                    </div>
                @endif

                <flux:button
                    wire:click="verifyCheckout"
                    variant="primary"
                    class="w-full"
                >
                    {{ __('Verify & Check Out') }}
                </flux:button>
            </div>

            {{-- Recently Checked Out --}}
            @if ($this->checkedOutChildren->isNotEmpty())
                <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Recently Checked Out') }}</flux:heading>
                    <div class="space-y-2">
                        @foreach ($this->checkedOutChildren as $record)
                            <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-700/50">
                                <flux:text class="font-medium text-zinc-900 dark:text-white">
                                    {{ $record->child?->fullName() }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $record->checked_out_at?->format('H:i') }}
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Active Children Tab --}}
    @if ($activeTab === 'active')
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Currently Checked In') }}</flux:heading>

            @if ($this->checkedInChildren->isEmpty())
                <div class="py-8 text-center">
                    <flux:icon name="user-group" class="mx-auto mb-2 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('No children currently checked in') }}
                    </flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Child') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Guardian') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Check-In Time') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Security Code') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($this->checkedInChildren as $record)
                                <tr wire:key="active-{{ $record->id }}">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($record->child?->photo_url)
                                                <img
                                                    src="{{ $record->child->photo_url }}"
                                                    alt="{{ $record->child->fullName() }}"
                                                    class="h-8 w-8 rounded-full object-cover"
                                                />
                                            @else
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                                    <flux:icon name="user" class="h-4 w-4 text-zinc-500 dark:text-zinc-400" />
                                                </div>
                                            @endif
                                            <div>
                                                <flux:text class="font-medium text-zinc-900 dark:text-white">
                                                    {{ $record->child?->fullName() }}
                                                </flux:text>
                                                @if ($record->child?->date_of_birth)
                                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                                        {{ __(':age years old', ['age' => $record->child->date_of_birth->age]) }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:text class="text-zinc-900 dark:text-white">
                                            {{ $record->guardian?->fullName() ?? __('Not specified') }}
                                        </flux:text>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:text class="text-zinc-900 dark:text-white">
                                            {{ $record->attendance?->check_in_time ? substr($record->attendance->check_in_time, 0, 5) : '-' }}
                                        </flux:text>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="rounded bg-zinc-100 px-2 py-1 font-mono text-lg dark:bg-zinc-700">
                                            {{ $record->security_code }}
                                        </code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Check-In Modal --}}
    <flux:modal wire:model="showCheckInModal" class="max-w-md">
        @if ($this->selectedChild)
            @if ($generatedSecurityCode)
                {{-- Security Code Display --}}
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <flux:icon name="check" class="h-8 w-8 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">{{ __('Check-In Successful!') }}</flux:heading>
                    <flux:text class="mb-4 text-zinc-500 dark:text-zinc-400">
                        {{ $this->selectedChild->fullName() }} {{ __('has been checked in.') }}
                    </flux:text>

                    <div class="mb-4 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-6 dark:border-zinc-600 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Security Code') }}</flux:text>
                        <div class="font-mono text-4xl font-bold tracking-widest text-zinc-900 dark:text-white">
                            {{ $generatedSecurityCode }}
                        </div>
                    </div>

                    <flux:text class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Give this code to the parent/guardian for child pickup.') }}
                    </flux:text>

                    <flux:button wire:click="closeCheckInModal" variant="primary" class="w-full">
                        {{ __('Done') }}
                    </flux:button>
                </div>
            @else
                {{-- Guardian Selection --}}
                <flux:heading size="lg" class="mb-4">{{ __('Check In :name', ['name' => $this->selectedChild->fullName()]) }}</flux:heading>

                <div class="mb-4">
                    <flux:field>
                        <flux:label>{{ __('Select Guardian') }}</flux:label>
                        @if ($this->availableGuardians->isEmpty())
                            <flux:text class="text-zinc-500 dark:text-zinc-400">
                                {{ __('No guardians found in household.') }}
                            </flux:text>
                        @else
                            <flux:select wire:model="selectedGuardianId">
                                @foreach ($this->availableGuardians as $guardian)
                                    <flux:select.option value="{{ $guardian->id }}">
                                        {{ $guardian->fullName() }}
                                        @if ($guardian->household_role)
                                            ({{ ucfirst($guardian->household_role->value) }})
                                        @endif
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:button wire:click="closeCheckInModal" variant="ghost" class="flex-1">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="checkInChild" variant="primary" class="flex-1">
                        {{ __('Check In') }}
                    </flux:button>
                </div>
            @endif
        @endif
    </flux:modal>

    {{-- Checkout Confirmation Modal --}}
    <flux:modal wire:model="showCheckoutModal" class="max-w-md">
        @if ($checkoutRecord)
            <div class="text-center">
                <flux:heading size="lg" class="mb-4">{{ __('Confirm Check-Out') }}</flux:heading>

                <div class="mb-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700/50">
                    @if ($checkoutRecord->child?->photo_url)
                        <img
                            src="{{ $checkoutRecord->child->photo_url }}"
                            alt="{{ $checkoutRecord->child->fullName() }}"
                            class="mx-auto mb-3 h-20 w-20 rounded-full object-cover"
                        />
                    @else
                        <div class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                            <flux:icon name="user" class="h-10 w-10 text-zinc-400" />
                        </div>
                    @endif
                    <flux:heading size="md">{{ $checkoutRecord->child?->fullName() }}</flux:heading>
                    @if ($checkoutRecord->guardian)
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ __('Guardian: :name', ['name' => $checkoutRecord->guardian->fullName()]) }}
                        </flux:text>
                    @endif
                </div>

                <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
                    {{ __('Are you sure you want to check out this child?') }}
                </flux:text>

                <div class="flex gap-3">
                    <flux:button wire:click="cancelCheckout" variant="ghost" class="flex-1">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="confirmCheckout" variant="primary" class="flex-1">
                        {{ __('Confirm Check-Out') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
