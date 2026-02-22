<section class="w-full">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('events.show', [$branch, $event]) }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                <flux:icon icon="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl" level="1">{{ __('Event Check-In') }}</flux:heading>
                <flux:subheading>{{ $event->name }}</flux:subheading>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Registered') }}</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total'] }}</div>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">{{ __('Checked In') }}</div>
            <div class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">{{ $this->stats['checked_in'] }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Pending') }}</div>
            <div class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $this->stats['pending'] }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4">
            <button
                wire:click="setActiveTab('search')"
                class="flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'search' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="magnifying-glass" class="size-4" />
                {{ __('Search') }}
            </button>
            <button
                wire:click="setActiveTab('qr')"
                class="flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'qr' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="qr-code" class="size-4" />
                {{ __('QR Scan') }}
            </button>
        </nav>
    </div>

    @if($activeTab === 'search')
    {{-- Search and Filter --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name or ticket number...') }}"
                icon="magnifying-glass"
                autofocus
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Registrations') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Pending Check-In') }}</flux:select.option>
                <flux:select.option value="checked_in">{{ __('Checked In') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Registrations List --}}
    @if($this->registrations->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 py-12 dark:border-zinc-700">
            <flux:icon icon="users" class="size-12 text-zinc-400" />
            <flux:heading size="md" class="mt-4">{{ __('No registrations found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $statusFilter)
                    {{ __('Try adjusting your search or filter.') }}
                @else
                    {{ __('No one has registered for this event yet.') }}
                @endif
            </flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->registrations as $registration)
                <div
                    wire:key="checkin-{{ $registration->id }}"
                    @class([
                        'flex items-center justify-between rounded-xl border p-4 transition-colors',
                        'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' => $registration->status === \App\Enums\RegistrationStatus::Attended,
                        'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' => $registration->status !== \App\Enums\RegistrationStatus::Attended,
                    ])
                >
                    <div class="flex items-center gap-4">
                        {{-- Status Indicator --}}
                        <div @class([
                            'flex size-10 items-center justify-center rounded-full',
                            'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-400' => $registration->status === \App\Enums\RegistrationStatus::Attended,
                            'bg-zinc-100 text-zinc-400 dark:bg-zinc-700 dark:text-zinc-500' => $registration->status !== \App\Enums\RegistrationStatus::Attended,
                        ])>
                            @if($registration->status === \App\Enums\RegistrationStatus::Attended)
                                <flux:icon icon="check" class="size-5" />
                            @else
                                <flux:icon icon="user" class="size-5" />
                            @endif
                        </div>

                        {{-- Attendee Info --}}
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $registration->attendee_name }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-zinc-500">
                                @if($registration->ticket_number)
                                    <span class="font-mono">{{ $registration->ticket_number }}</span>
                                    <span>&bull;</span>
                                @endif
                                <span>{{ ucfirst($registration->attendee_type) }}</span>
                                @if($registration->check_in_time)
                                    <span>&bull;</span>
                                    <span>{{ __('Checked in at :time', ['time' => $registration->check_in_time->format('g:i A')]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($registration->status === \App\Enums\RegistrationStatus::Registered)
                            <flux:button variant="primary" wire:click="checkIn('{{ $registration->id }}')" icon="check">
                                {{ __('Check In') }}
                            </flux:button>
                        @elseif($registration->status === \App\Enums\RegistrationStatus::Attended)
                            @if(!$registration->is_checked_out)
                                <flux:button variant="ghost" wire:click="checkOut('{{ $registration->id }}')" icon="arrow-left-start-on-rectangle">
                                    {{ __('Check Out') }}
                                </flux:button>
                            @endif
                            <flux:button variant="ghost" wire:click="undoCheckIn('{{ $registration->id }}')" icon="arrow-uturn-left">
                                {{ __('Undo') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @endif

    @if($activeTab === 'qr')
    {{-- QR Scanner Tab --}}
    <div
        x-data="eventQrScanner(@this)"
        class="flex flex-col items-center"
    >
        @if($qrError)
            <div class="mb-4 w-full max-w-md rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30">
                <div class="flex items-center gap-2">
                    <flux:icon icon="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                    <flux:text class="text-red-700 dark:text-red-300">{{ $qrError }}</flux:text>
                </div>
            </div>
        @endif

        <div class="relative w-full max-w-md overflow-hidden rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-900 dark:border-zinc-600" style="aspect-ratio: 1;">
            <div id="event-qr-reader" x-show="$wire.isScanning" class="size-full"></div>

            @if(!$isScanning)
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon icon="qr-code" class="size-20 text-zinc-400" />
                    <flux:text class="mt-4 text-zinc-500">{{ __('Camera not active') }}</flux:text>
                </div>
            @endif
        </div>

        <div class="mt-4 flex gap-2">
            @if(!$isScanning)
                <flux:button variant="primary" icon="play" x-on:click="startScanning">
                    {{ __('Start Scanning') }}
                </flux:button>
            @else
                <flux:button variant="danger" icon="stop" x-on:click="stopScanning">
                    {{ __('Stop Scanning') }}
                </flux:button>
            @endif
        </div>

        <flux:text class="mt-4 text-center text-sm text-zinc-500">
            {{ __('Point the camera at an attendee\'s ticket QR code to check them in.') }}
        </flux:text>
    </div>

    @script
    <script>
        Alpine.data('eventQrScanner', (component) => ({
            scanner: null,

            async startScanning() {
                component.startScanning();

                await this.$nextTick();

                this.scanner = new window.Html5Qrcode('event-qr-reader');

                try {
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText) => {
                            component.$dispatch('qr-scanned', { code: decodedText });
                            // Brief pause after successful scan
                            this.scanner.pause(true);
                            setTimeout(() => {
                                if (this.scanner && this.scanner.getState() === 3) {
                                    this.scanner.resume();
                                }
                            }, 2000);
                        },
                        () => {}
                    );
                } catch (err) {
                    console.error('QR Scanner error:', err);
                    component.stopScanning();
                }
            },

            async stopScanning() {
                if (this.scanner) {
                    try {
                        await this.scanner.stop();
                    } catch (err) {
                        console.error('Error stopping scanner:', err);
                    }
                    this.scanner = null;
                }
                component.stopScanning();
            },

            destroy() {
                this.stopScanning();
            }
        }));
    </script>
    @endscript
    @endif

    {{-- Toasts --}}
    <x-toast on="checked-in" type="success">
        {{ __(':name checked in successfully.', ['name' => '$event.detail.name']) }}
    </x-toast>

    <x-toast on="checked-out" type="success">
        {{ __('Attendee checked out successfully.') }}
    </x-toast>

    <x-toast on="check-in-undone" type="info">
        {{ __('Check-in undone.') }}
    </x-toast>

    <x-toast on="qr-error" type="error">
        {{ __('Ticket not found.') }}
    </x-toast>

    <x-toast on="already-checked-in" type="info">
        {{ __('This person is already checked in.') }}
    </x-toast>
</section>
