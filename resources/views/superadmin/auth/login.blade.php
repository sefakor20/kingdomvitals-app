<x-layouts.superadmin.auth>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <flux:heading size="lg" class="text-white">Administrator Login</flux:heading>
            <flux:text class="mt-2 text-slate-400">Sign in to access the platform administration panel</flux:text>
        </div>

        <form method="POST" action="{{ route('superadmin.login') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                type="email"
                :label="__('Email')"
                placeholder="admin@example.com"
                required
                autofocus
                autocomplete="email"
                :value="old('email')"
            />

            <flux:input
                name="password"
                type="password"
                :label="__('Password')"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            />

            <flux:checkbox name="remember" :label="__('Remember me')" />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Sign in') }}
            </flux:button>
        </form>
    </div>
</x-layouts.superadmin.auth>
