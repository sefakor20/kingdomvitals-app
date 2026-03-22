<div>
    <div class="mb-8">
        <flux:heading size="xl">{{ __('My Profile') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('View and update your profile information.') }}
        </flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profile Overview (Read-only) --}}
        <flux:card class="lg:col-span-1">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Member Information') }}</flux:heading>
            </div>

            <div class="space-y-4 p-4">
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Full Name') }}</flux:text>
                    <flux:heading size="lg">{{ $this->member->fullName() }}</flux:heading>
                </div>

                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Membership Number') }}</flux:text>
                    <div class="font-medium">{{ $this->member->membership_number }}</div>
                </div>

                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Email') }}</flux:text>
                    <div class="font-medium">{{ $this->member->email }}</div>
                </div>

                @if($this->member->date_of_birth)
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Date of Birth') }}</flux:text>
                        <div class="font-medium">{{ $this->member->date_of_birth->format('M d, Y') }}</div>
                    </div>
                @endif

                @if($this->member->gender)
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Gender') }}</flux:text>
                        <div class="font-medium">{{ __($this->member->gender->name) }}</div>
                    </div>
                @endif

                @if($this->member->marital_status)
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Marital Status') }}</flux:text>
                        <div class="font-medium">{{ __($this->member->marital_status->name) }}</div>
                    </div>
                @endif

                @if($this->member->joined_at)
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Member Since') }}</flux:text>
                        <div class="font-medium">{{ $this->member->joined_at->format('M d, Y') }}</div>
                    </div>
                @endif

                <div class="pt-4 text-sm text-zinc-500">
                    {{ __('To update your name, email, date of birth, or other personal details, please contact your church administrator.') }}
                </div>
            </div>
        </flux:card>

        {{-- Editable Contact Info --}}
        <flux:card class="lg:col-span-2">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Contact Information') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-4 p-4">
                <flux:input
                    wire:model="phone"
                    :label="__('Phone Number')"
                    type="tel"
                    :placeholder="__('Enter your phone number')"
                />

                <flux:input
                    wire:model="profession"
                    :label="__('Profession')"
                    type="text"
                    :placeholder="__('What do you do?')"
                />

                <flux:separator />

                <flux:heading size="sm">{{ __('Address') }}</flux:heading>

                <flux:input
                    wire:model="address"
                    :label="__('Street Address')"
                    type="text"
                    :placeholder="__('Enter your address')"
                />

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input
                        wire:model="city"
                        :label="__('City')"
                        type="text"
                        :placeholder="__('City')"
                    />

                    <flux:input
                        wire:model="state"
                        :label="__('State/Region')"
                        type="text"
                        :placeholder="__('State')"
                    />

                    <flux:input
                        wire:model="zip"
                        :label="__('ZIP/Postal Code')"
                        type="text"
                        :placeholder="__('ZIP Code')"
                    />
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>
