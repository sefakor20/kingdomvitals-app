<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('member.dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            @php
                $member = app('currentMember');
            @endphp

            {{-- Member Info --}}
            <div class="mb-4 rounded-lg bg-white p-3 dark:bg-zinc-800">
                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $member->fullName() }}</div>
                <div class="text-xs text-zinc-500">{{ $member->membership_number }}</div>
                <div class="mt-1 text-xs text-zinc-400">{{ $member->primaryBranch?->name }}</div>
            </div>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('My Portal')" class="grid">
                    <flux:navlist.item icon="home" :href="route('member.dashboard')" :current="request()->routeIs('member.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user" :href="route('member.profile')" :current="request()->routeIs('member.profile')" wire:navigate>
                        {{ __('My Profile') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('My Activity')" class="grid">
                    <flux:navlist.item icon="banknotes" :href="route('member.giving')" :current="request()->routeIs('member.giving')" wire:navigate>
                        {{ __('Giving') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" :href="route('member.attendance')" :current="request()->routeIs('member.attendance')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-check" :href="route('member.pledges')" :current="request()->routeIs('member.pledges')" wire:navigate>
                        {{ __('Pledges') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="ticket" :href="route('member.events')" :current="request()->routeIs('member.events')" wire:navigate>
                        {{ __('Events') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            {{-- Quick Actions --}}
            <div class="mb-4">
                <flux:button href="{{ route('giving.form', $member->primaryBranch) }}" variant="primary" class="w-full" icon="heart">
                    {{ __('Give Now') }}
                </flux:button>
            </div>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('member.profile')" icon="user" wire:navigate>{{ __('My Profile') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
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
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.item :href="route('member.profile')" icon="user" wire:navigate>{{ __('My Profile') }}</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
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
