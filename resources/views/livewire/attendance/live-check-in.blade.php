<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Live Check-in') }}</flux:heading>
            <flux:subheading>{{ $service->name }} - {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('attendance.dashboard', [$branch, $service])" icon="chart-bar" wire:navigate>
                {{ __('Dashboard') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('services.show', [$branch, $service])" icon="arrow-left" wire:navigate>
                {{ __('Back to Service') }}
            </flux:button>
        </div>
    </div>

    <!-- Stats -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="clipboard-document-check" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="user-group" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['members']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="user-plus" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['visitors']) }}</flux:heading>
        </div>
    </div>

    <!-- Tabs -->
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
            <button
                wire:click="setActiveTab('family')"
                class="flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'family' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                <flux:icon icon="home" class="size-4" />
                {{ __('Family') }}
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="mb-8">
        @if($activeTab === 'search')
            <!-- Search Tab -->
            <div>
                <flux:input
                    wire:model.live.debounce.200ms="searchQuery"
                    placeholder="{{ __('Search member or visitor name...') }}"
                    icon="magnifying-glass"
                    class="!py-4 !text-lg"
                />
                @if(strlen($searchQuery) > 0 && strlen($searchQuery) < 2)
                    <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Type at least 2 characters to search...') }}</flux:text>
                @endif

                <!-- Search Results Grid -->
                @if($this->searchResults->isNotEmpty())
                    <div class="mt-6">
                        <flux:text class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            {{ __('Select to check in:') }}
                        </flux:text>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            @foreach($this->searchResults as $result)
                                <button
                                    wire:key="result-{{ $result['type'] }}-{{ $result['id'] }}"
                                    wire:click="checkIn('{{ $result['id'] }}', '{{ $result['type'] }}')"
                                    @if($result['already_checked_in']) disabled @endif
                                    class="flex flex-col items-center rounded-xl border-2 p-4 text-center transition-all
                                        {{ $result['already_checked_in']
                                            ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-60 dark:border-zinc-700 dark:bg-zinc-800'
                                            : ($result['type'] === 'member'
                                                ? 'border-green-200 bg-green-50 hover:border-green-400 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/30 dark:hover:border-green-600'
                                                : 'border-purple-200 bg-purple-50 hover:border-purple-400 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/30 dark:hover:border-purple-600')
                                        }}"
                                >
                                    @if($result['photo_url'])
                                        <img src="{{ $result['photo_url'] }}" alt="{{ $result['name'] }}" class="size-12 rounded-full object-cover {{ $result['type'] === 'visitor' ? 'ring-2 ring-purple-400' : '' }}" />
                                    @else
                                        <flux:avatar
                                            size="lg"
                                            name="{{ $result['name'] }}"
                                            class="{{ $result['type'] === 'visitor' ? 'ring-2 ring-purple-400' : '' }}"
                                        />
                                    @endif
                                    <span class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $result['name'] }}
                                    </span>
                                    <span class="text-xs {{ $result['type'] === 'member' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}">
                                        {{ $result['type'] === 'member' ? __('Member') : __('Visitor') }}
                                    </span>
                                    @if($result['already_checked_in'])
                                        <flux:badge color="zinc" size="sm" class="mt-1">
                                            {{ __('Already checked in') }}
                                        </flux:badge>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @elseif(strlen($searchQuery) >= 2)
                    <div class="mt-6 flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon icon="magnifying-glass" class="size-8 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('No members or visitors found matching ":search"', ['search' => $searchQuery]) }}</flux:text>
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'qr')
            <!-- QR Scanner Tab -->
            <div
                x-data="qrScanner(@this)"
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
                    <div id="qr-reader" x-show="$wire.isScanning" class="size-full"></div>

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
                    {{ __('Point the camera at a member\'s QR code to check them in.') }}
                </flux:text>
            </div>

            @script
            <script>
                Alpine.data('qrScanner', (component) => ({
                    scanner: null,

                    async startScanning() {
                        component.startScanning();

                        await this.$nextTick();

                        this.scanner = new window.Html5Qrcode('qr-reader');

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

        @elseif($activeTab === 'family')
            <!-- Family Check-in Tab -->
            <div>
                <flux:input
                    wire:model.live.debounce.200ms="familySearchQuery"
                    placeholder="{{ __('Search family/household name...') }}"
                    icon="home"
                    class="!py-4 !text-lg"
                />
                @if(strlen($familySearchQuery) > 0 && strlen($familySearchQuery) < 2)
                    <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Type at least 2 characters to search...') }}</flux:text>
                @endif

                <!-- Household Search Results -->
                @if($this->householdSearchResults->isNotEmpty())
                    <div class="mt-6">
                        <flux:text class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            {{ __('Select a family to check in:') }}
                        </flux:text>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($this->householdSearchResults as $household)
                                <button
                                    wire:key="household-{{ $household->id }}"
                                    wire:click="openFamilyModal('{{ $household->id }}')"
                                    class="flex items-center gap-4 rounded-xl border-2 border-blue-200 bg-blue-50 p-4 text-left transition-all hover:border-blue-400 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/30 dark:hover:border-blue-600"
                                >
                                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                                        <flux:icon icon="home" class="size-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $household->name }}
                                        </flux:text>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ trans_choice(':count member|:count members', $household->members_count) }}
                                        </flux:text>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @elseif(strlen($familySearchQuery) >= 2)
                    <div class="mt-6 flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon icon="home" class="size-8 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('No families found matching ":search"', ['search' => $familySearchQuery]) }}</flux:text>
                        <flux:text class="mt-1 text-sm text-zinc-400">{{ __('Families can be managed in the Households section.') }}</flux:text>
                    </div>
                @else
                    <div class="mt-6 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 py-12 dark:border-zinc-600">
                        <flux:icon icon="home" class="size-16 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">{{ __('Family Check-in') }}</flux:heading>
                        <flux:text class="mt-2 text-center text-zinc-500">
                            {{ __('Search for a family to check in all members at once.') }}
                        </flux:text>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Empty State / Instructions (only show on search tab when empty) -->
    @if($activeTab === 'search' && strlen($searchQuery) < 2 && $this->recentCheckIns->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 py-12 dark:border-zinc-600">
            <flux:icon icon="hand-raised" class="size-16 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('Ready for Check-ins') }}</flux:heading>
            <flux:text class="mt-2 text-center text-zinc-500">
                {{ __('Start typing a name to find members or visitors to check in.') }}
            </flux:text>
        </div>
    @endif

    <!-- Recent Check-ins -->
    @if($this->recentCheckIns->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Check-ins') }}</flux:heading>
            <div class="space-y-3">
                @foreach($this->recentCheckIns as $checkIn)
                    <div wire:key="recent-{{ $loop->index }}" class="flex items-center gap-3">
                        @if($checkIn['photo_url'])
                            <img src="{{ $checkIn['photo_url'] }}" alt="{{ $checkIn['name'] }}" class="size-8 rounded-full object-cover" />
                        @else
                            <div class="flex size-8 items-center justify-center rounded-full {{ $checkIn['type'] === 'member' ? 'bg-green-100 dark:bg-green-900' : 'bg-purple-100 dark:bg-purple-900' }}">
                                <flux:icon icon="check" class="size-4 {{ $checkIn['type'] === 'member' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}" />
                            </div>
                        @endif
                        <div class="flex-1">
                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $checkIn['name'] }}
                            </flux:text>
                        </div>
                        <flux:badge
                            :color="$checkIn['type'] === 'member' ? 'green' : 'purple'"
                            size="sm"
                        >
                            {{ $checkIn['type'] === 'member' ? __('Member') : __('Visitor') }}
                        </flux:badge>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $checkIn['time'] }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Family Check-in Modal -->
    <flux:modal wire:model="showFamilyModal" class="max-w-lg">
        @if($this->selectedHousehold)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $this->selectedHousehold->name }}</flux:heading>
                    <flux:subheading>{{ __('Select family members to check in') }}</flux:subheading>
                </div>

                <div class="space-y-2">
                    @foreach($this->selectedHousehold->members as $member)
                        @php
                            $isCheckedIn = $this->isAlreadyCheckedIn('member', $member->id);
                            $isSelected = in_array($member->id, $selectedFamilyMembers);
                        @endphp
                        <button
                            wire:key="family-member-{{ $member->id }}"
                            wire:click="toggleFamilyMember('{{ $member->id }}')"
                            @if($isCheckedIn) disabled @endif
                            class="flex w-full items-center gap-3 rounded-lg border-2 p-3 transition-all
                                {{ $isCheckedIn
                                    ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-60 dark:border-zinc-700 dark:bg-zinc-800'
                                    : ($isSelected
                                        ? 'border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-900/30'
                                        : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600')
                                }}"
                        >
                            <div class="flex size-6 items-center justify-center rounded border {{ $isSelected ? 'border-green-500 bg-green-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                                @if($isSelected)
                                    <flux:icon icon="check" class="size-4 text-white" />
                                @endif
                            </div>

                            @if($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-10 rounded-full object-cover" />
                            @else
                                <flux:avatar name="{{ $member->fullName() }}" />
                            @endif

                            <div class="flex-1 text-left">
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $member->fullName() }}
                                </flux:text>
                                @if($member->household_role)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ ucfirst($member->household_role->value) }}
                                    </flux:text>
                                @endif
                            </div>

                            @if($isCheckedIn)
                                <flux:badge color="zinc" size="sm">{{ __('Checked in') }}</flux:badge>
                            @elseif($member->isChild())
                                <flux:badge color="amber" size="sm">{{ __('Child') }}</flux:badge>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeFamilyModal">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="checkInSelectedFamily"
                        :disabled="empty($selectedFamilyMembers)"
                    >
                        {{ __('Check In Selected') }} ({{ count($selectedFamilyMembers) }})
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Success Toast -->
    <x-toast on="check-in-success" type="success">
        {{ __('Checked in successfully!') }}
    </x-toast>

    <!-- Already Checked In Toast -->
    <x-toast on="already-checked-in" type="warning">
        {{ __('This person is already checked in for today.') }}
    </x-toast>

    <!-- QR Error Toast -->
    <x-toast on="qr-error" type="error">
        {{ __('QR code scan failed.') }}
    </x-toast>

    <!-- Family Check-in Success Toast -->
    <x-toast on="family-check-in-success" type="success">
        {{ __('Family checked in successfully!') }}
    </x-toast>
</section>
