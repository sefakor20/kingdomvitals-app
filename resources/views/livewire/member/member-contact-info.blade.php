<section class="w-full">
    @include('partials.member-settings-heading')

    <x-settings.member-layout :heading="__('Contact Information')" :subheading="__('Update your contact details and address')">
        <form wire:submit="save" class="space-y-4">
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
    </x-settings.member-layout>
</section>
