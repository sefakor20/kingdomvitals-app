<div class="flex flex-col gap-6">
    @if(!$invitationValid)
        {{-- Invalid or expired invitation --}}
        <x-auth-header
            :title="__('Invalid Invitation')"
            :description="__('This invitation link is invalid or has expired.')"
        />

        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center dark:border-red-800 dark:bg-red-900/20">
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Please contact your church administrator to request a new invitation.') }}
            </flux:text>
        </div>

        <flux:button href="/" variant="primary" class="w-full" wire:navigate>
            {{ __('Go to Home') }}
        </flux:button>
    @else
        {{-- Valid invitation --}}
        <x-auth-header
            :title="__('Member Portal Access')"
            :description="__('You\'ve been invited to access the member portal for :branch.', ['branch' => $invitation->branch->name])"
        />

        @if($invitation->invitedBy)
            <flux:text class="-mt-4 text-center text-sm text-zinc-500">
                {{ __('Invited by :name', ['name' => $invitation->invitedBy->name]) }}
            </flux:text>
        @endif

        {{-- Member info preview --}}
        @if($invitation->member)
            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Your Member Profile') }}</flux:text>
                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $invitation->member->fullName() }}</div>
                @if($invitation->member->membership_number)
                    <flux:text class="text-sm text-zinc-500">{{ $invitation->member->membership_number }}</flux:text>
                @endif
            </div>
        @endif

        @if($userExists)
            {{-- User already exists - just confirm to accept --}}
            <flux:text class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('An account already exists for :email. Click below to activate your member portal access.', ['email' => $email]) }}
            </flux:text>

            <form wire:submit="accept">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Activate Portal Access') }}
                </flux:button>
            </form>
        @else
            {{-- New user - show registration form --}}
            <form wire:submit="accept" class="flex flex-col gap-4">
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
                    class="bg-zinc-50 dark:bg-zinc-800"
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
                    {{ __('Create Account & Access Portal') }}
                </flux:button>
            </form>
        @endif

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link href="/login" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    @endif
</div>
