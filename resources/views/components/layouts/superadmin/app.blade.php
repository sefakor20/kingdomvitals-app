<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('superadmin.dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600">
                    <flux:icon.shield-check class="size-5 text-white" />
                </div>
                <span class="text-lg font-semibold text-zinc-900 dark:text-white">Admin Panel</span>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Overview')" class="grid">
                    <flux:navlist.item icon="home" :href="route('superadmin.dashboard')" :current="request()->routeIs('superadmin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Management')" class="grid">
                    <flux:navlist.item icon="building-office-2" :href="route('superadmin.tenants.index')" :current="request()->routeIs('superadmin.tenants.*')" wire:navigate>
                        {{ __('Tenants') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="credit-card" :href="route('superadmin.plans.index')" :current="request()->routeIs('superadmin.plans.*')" wire:navigate>
                        {{ __('Subscription Plans') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('System')" class="grid">
                    <flux:navlist.item icon="clipboard-document-list" :href="route('superadmin.activity-logs')" :current="request()->routeIs('superadmin.activity-logs')" wire:navigate>
                        {{ __('Activity Logs') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth('superadmin')->user()->name"
                    :initials="substr(auth('superadmin')->user()->name, 0, 2)"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-indigo-600 text-white">
                                        {{ substr(auth('superadmin')->user()->name, 0, 2) }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth('superadmin')->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth('superadmin')->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <div class="px-2 py-1">
                            <flux:badge color="indigo" size="sm">
                                {{ auth('superadmin')->user()->role->label() }}
                            </flux:badge>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('superadmin.profile.security')" icon="shield-check" wire:navigate>
                        {{ __('Security') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('superadmin.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="substr(auth('superadmin')->user()->name, 0, 2)"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-indigo-600 text-white">
                                        {{ substr(auth('superadmin')->user()->name, 0, 2) }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth('superadmin')->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth('superadmin')->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('superadmin.profile.security')" icon="shield-check" wire:navigate>
                        {{ __('Security') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('superadmin.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
