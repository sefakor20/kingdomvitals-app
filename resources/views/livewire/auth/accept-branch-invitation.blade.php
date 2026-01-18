<div class="flex flex-col gap-6">
    @if(!$invitationValid)
        {{-- Invalid or expired invitation --}}
        <div class="rounded-lg border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-900/20">
            <flux:icon name="exclamation-triangle" class="mx-auto mb-4 size-12 text-red-500" />
            <flux:heading size="lg" class="mb-2">{{ __('Invalid Invitation') }}</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ __('This invitation link is invalid or has expired. Please contact the person who invited you to request a new invitation.') }}
            </flux:text>
            <div class="mt-6">
                <flux:button href="/" variant="primary" wire:navigate>
                    {{ __('Go to Home') }}
                </flux:button>
            </div>
        </div>
    @else
        {{-- Valid invitation --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6 text-center">
                <flux:heading size="xl">{{ __('You\'re Invited!') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('You\'ve been invited to join :branch as a :role.', [
                        'branch' => $invitation->branch->name,
                        'role' => ucfirst($invitation->role->value),
                    ]) }}
                </flux:text>
                @if($invitation->invitedBy)
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        {{ __('Invited by :name', ['name' => $invitation->invitedBy->name]) }}
                    </flux:text>
                @endif
            </div>

            @if($userExists)
                {{-- User already exists - just confirm to accept --}}
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <flux:text class="mb-4 text-center text-zinc-600 dark:text-zinc-400">
                        {{ __('An account already exists for :email. Click below to accept the invitation and access the branch.', ['email' => $email]) }}
                    </flux:text>
                    <form wire:submit="accept">
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ __('Accept Invitation') }}
                        </flux:button>
                    </form>
                </div>
            @else
                {{-- New user - show registration form --}}
                <form wire:submit="accept" class="space-y-4">
                    <flux:input
                        wire:model="name"
                        :label="__('Your Name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        :placeholder="__('Full name')"
                    />

                    <flux:input
                        :value="$email"
                        :label="__('Email Address')"
                        type="email"
                        disabled
                        class="bg-zinc-50 dark:bg-zinc-900"
                    />

                    <flux:input
                        wire:model="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Create a password')"
                        viewable
                    />

                    <flux:input
                        wire:model="password_confirmation"
                        :label="__('Confirm Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Confirm your password')"
                        viewable
                    />

                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Create Account & Accept') }}
                    </flux:button>
                </form>
            @endif
        </div>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link href="/login" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    @endif
</div>
