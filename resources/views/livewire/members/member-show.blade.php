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
                <flux:button variant="primary" href="{{ route('members.index', $branch) }}" icon="pencil">
                    {{ __('Edit') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Member Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg" name="{{ $member->fullName() }}" />
                <div>
                    <flux:heading size="xl">{{ $member->fullName() }}</flux:heading>
                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($member->gender)
                            <span>{{ ucfirst($member->gender->value) }}</span>
                        @endif
                        @if($member->gender && $member->marital_status)
                            <span>&bull;</span>
                        @endif
                        @if($member->marital_status)
                            <span>{{ ucfirst($member->marital_status->value) }}</span>
                        @endif
                    </div>
                </div>
            </div>
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
                        {{ $member->date_of_birth?->format('M d, Y') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $member->gender ? ucfirst($member->gender->value) : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Marital Status') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $member->marital_status ? ucfirst($member->marital_status->value) : '-' }}
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
                        @if($member->email)
                            <a href="mailto:{{ $member->email }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $member->email }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($member->phone)
                            <a href="tel:{{ $member->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $member->phone }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Address -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Address') }}</flux:heading>
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
        </div>

        <!-- Church Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Church Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Baptized') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $member->baptized_at?->format('M d, Y') ?? '-' }}
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
    @if($member->notes)
        <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Notes') }}</flux:heading>
            <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $member->notes }}</p>
        </div>
    @endif
</section>
