<section class="w-full">
    @include('partials.member-settings-heading')

    <x-settings.member-layout :heading="__('Password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="space-y-4">
            <flux:input
                wire:model="current_password"
                :label="__('Current Password')"
                type="password"
                required
                autocomplete="current-password"
            />

            <flux:input
                wire:model="password"
                :label="__('New Password')"
                type="password"
                required
                autocomplete="new-password"
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm New Password')"
                type="password"
                required
                autocomplete="new-password"
            />

            <div class="flex justify-end pt-4">
                <flux:button type="submit" variant="primary">
                    {{ __('Update Password') }}
                </flux:button>
            </div>
        </form>
    </x-settings.member-layout>
</section>
