<div class="min-h-screen bg-zinc-50 px-4 py-8 dark:bg-zinc-900">
    <div class="mx-auto max-w-md">
        @if (! $member)
            {{-- Invalid Token --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                    <flux:icon name="exclamation-triangle" class="h-8 w-8 text-red-600 dark:text-red-400" />
                </div>
                <flux:heading size="lg">{{ __('Invalid Link') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                    {{ __('This check-in link is invalid or has expired. Please contact your church administrator for a new link.') }}
                </flux:text>
            </div>
        @else
            {{-- Member Info --}}
            <div class="mb-6 text-center">
                @if ($member->photo_url)
                    <img
                        src="{{ $member->photo_url }}"
                        alt="{{ $member->fullName() }}"
                        class="mx-auto mb-4 h-24 w-24 rounded-full object-cover ring-4 ring-white dark:ring-zinc-700"
                    />
                @else
                    <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-zinc-200 ring-4 ring-white dark:bg-zinc-700 dark:ring-zinc-700">
                        <flux:icon name="user" class="h-12 w-12 text-zinc-400" />
                    </div>
                @endif
                <flux:heading size="xl">{{ $member->fullName() }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $member->primaryBranch?->name }}
                </flux:text>
            </div>

            {{-- Success Message --}}
            @if ($showSuccess)
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                            <flux:icon name="check" class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium text-green-800 dark:text-green-200">
                                {{ __('Check-in Successful!') }}
                            </flux:text>
                            <flux:text class="text-sm text-green-600 dark:text-green-400">
                                {{ $successMessage }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if ($errorMessage)
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <div class="flex items-center gap-3">
                        <flux:icon name="exclamation-circle" class="h-6 w-6 shrink-0 text-red-600 dark:text-red-400" />
                        <flux:text class="text-red-800 dark:text-red-200">
                            {{ $errorMessage }}
                        </flux:text>
                    </div>
                </div>
            @endif

            {{-- QR Code Display --}}
            <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-4 text-center">{{ __('Your Check-In QR Code') }}</flux:heading>

                <div class="mx-auto mb-4 flex aspect-square max-w-[280px] items-center justify-center rounded-xl bg-white p-4">
                    @if ($this->qrCodeSvg)
                        {!! $this->qrCodeSvg !!}
                    @else
                        <flux:text class="text-zinc-400">{{ __('QR Code not available') }}</flux:text>
                    @endif
                </div>

                <flux:text class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Show this code at the check-in kiosk') }}
                </flux:text>

                <div class="mt-4 flex justify-center">
                    <flux:button
                        wire:click="regenerateQrCode"
                        variant="ghost"
                        size="sm"
                        icon="arrow-path"
                    >
                        {{ __('Regenerate Code') }}
                    </flux:button>
                </div>
            </div>

            {{-- Self Check-In --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-4">{{ __('Quick Self Check-In') }}</flux:heading>

                @if ($this->availableServices->isEmpty())
                    <div class="py-4 text-center">
                        <flux:icon name="calendar" class="mx-auto mb-2 h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ __('No services available today') }}
                        </flux:text>
                    </div>
                @else
                    <div class="mb-4 space-y-2">
                        @foreach ($this->availableServices as $service)
                            <label
                                wire:key="service-{{ $service['id'] }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition {{ $service['is_checked_in'] ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-700/50' }}"
                            >
                                @if ($service['is_checked_in'])
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                        <flux:icon name="check" class="h-3 w-3 text-white" />
                                    </div>
                                @else
                                    <input
                                        type="radio"
                                        wire:model="selectedServiceId"
                                        value="{{ $service['id'] }}"
                                        class="h-5 w-5 text-blue-600"
                                    />
                                @endif
                                <div class="flex-1">
                                    <flux:text class="font-medium {{ $service['is_checked_in'] ? 'text-green-800 dark:text-green-200' : 'text-zinc-900 dark:text-white' }}">
                                        {{ $service['name'] }}
                                    </flux:text>
                                    <flux:text class="text-sm {{ $service['is_checked_in'] ? 'text-green-600 dark:text-green-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                        @if ($service['is_checked_in'])
                                            {{ __('Already checked in') }}
                                        @else
                                            {{ $service['time'] ? substr($service['time'], 0, 5) : '' }}
                                        @endif
                                    </flux:text>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <flux:button
                        wire:click="selfCheckIn"
                        variant="primary"
                        class="w-full"
                        :disabled="! $selectedServiceId"
                        icon="check-circle"
                    >
                        {{ __('Check Me In') }}
                    </flux:button>
                @endif
            </div>
        @endif
    </div>
</div>

