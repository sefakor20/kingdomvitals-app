<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('members.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" icon="check">
                        {{ __('Save') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
        </div>
    </div>

    <!-- Member Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                @if($member->photo_url)
                    <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-16 rounded-full object-cover" />
                @else
                    <flux:avatar size="lg" name="{{ $member->fullName() }}" />
                @endif
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="first_name" placeholder="{{ __('First Name') }}" class="w-32" />
                            <flux:input wire:model="middle_name" placeholder="{{ __('Middle Name') }}" class="w-32" />
                            <flux:input wire:model="last_name" placeholder="{{ __('Last Name') }}" class="w-32" />
                        </div>
                        @error('first_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @error('last_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $member->fullName() }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($editing)
                            <flux:select wire:model="gender" placeholder="{{ __('Gender') }}" class="w-28">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->genders as $genderOption)
                                    <flux:select.option value="{{ $genderOption->value }}">{{ ucfirst($genderOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="marital_status" placeholder="{{ __('Marital Status') }}" class="w-32">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->maritalStatuses as $maritalOption)
                                    <flux:select.option value="{{ $maritalOption->value }}">{{ ucfirst($maritalOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            @if($member->gender)
                                <span>{{ ucfirst($member->gender->value) }}</span>
                            @endif
                            @if($member->gender && $member->marital_status)
                                <span>&bull;</span>
                            @endif
                            @if($member->marital_status)
                                <span>{{ ucfirst($member->marital_status->value) }}</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @if($editing)
                <flux:select wire:model="status" class="w-36">
                    @foreach($this->statuses as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:badge
                    :color="match($member->status->value) {
                        'active' => 'green',
                        'inactive' => 'zinc',
                        'pending' => 'yellow',
                        'deceased' => 'red',
                        'transferred' => 'blue',
                        default => 'zinc',
                    }"
                    size="lg"
                >
                    {{ ucfirst($member->status->value) }}
                </flux:badge>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Personal Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Personal Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="date_of_birth" />
                        @else
                            {{ $member->date_of_birth?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="gender">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->genders as $genderOption)
                                    <flux:select.option value="{{ $genderOption->value }}">{{ ucfirst($genderOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            {{ $member->gender ? ucfirst($member->gender->value) : '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Marital Status') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="marital_status">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->maritalStatuses as $maritalOption)
                                    <flux:select.option value="{{ $maritalOption->value }}">{{ ucfirst($maritalOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            {{ $member->marital_status ? ucfirst($member->marital_status->value) : '-' }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Contact Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Contact Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="email" wire:model="email" placeholder="{{ __('Email') }}" />
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @else
                            @if($member->email)
                                <a href="mailto:{{ $member->email }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $member->email }}
                                </a>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="tel" wire:model="phone" placeholder="{{ __('Phone') }}" />
                        @else
                            @if($member->phone)
                                <a href="tel:{{ $member->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $member->phone }}
                                </a>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Address -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Address') }}</flux:heading>
            @if($editing)
                <div class="grid gap-4">
                    <flux:input wire:model="address" placeholder="{{ __('Street Address') }}" />
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="city" placeholder="{{ __('City') }}" />
                        <flux:input wire:model="state" placeholder="{{ __('State/Region') }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="zip" placeholder="{{ __('ZIP/Postal Code') }}" />
                        <flux:input wire:model="country" placeholder="{{ __('Country') }}" />
                    </div>
                </div>
            @else
                @if($member->address || $member->city || $member->state || $member->zip || $member->country)
                    <address class="not-italic text-sm text-zinc-900 dark:text-zinc-100">
                        @if($member->address)
                            <div>{{ $member->address }}</div>
                        @endif
                        @if($member->city || $member->state || $member->zip)
                            <div>
                                {{ collect([$member->city, $member->state])->filter()->implode(', ') }}
                                @if($member->zip)
                                    {{ $member->zip }}
                                @endif
                            </div>
                        @endif
                        @if($member->country)
                            <div>{{ $member->country }}</div>
                        @endif
                    </address>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No address on file') }}</p>
                @endif
            @endif
        </div>

        <!-- Church Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Church Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="joined_at" />
                        @else
                            {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Baptized') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="baptized_at" />
                        @else
                            {{ $member->baptized_at?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Primary Branch') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $branch->name }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Notes -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Notes') }}</flux:heading>
        @if($editing)
            <flux:textarea wire:model="notes" placeholder="{{ __('Add notes about this member...') }}" rows="4" />
        @else
            @if($member->notes)
                <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $member->notes }}</p>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notes') }}</p>
            @endif
        @endif
    </div>
</section>
