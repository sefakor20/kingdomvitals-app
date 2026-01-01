<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('visitors.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if(!$visitor->is_converted && $this->canEdit)
                <flux:button variant="ghost" wire:click="openConvertModal" icon="arrow-right-circle" class="text-purple-600 hover:text-purple-700">
                    {{ __('Convert to Member') }}
                </flux:button>
            @endif
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1">
                            <flux:icon.check class="size-4" />
                            {{ __('Save') }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
            @if($this->canDelete && !$editing)
                <flux:button variant="danger" wire:click="confirmDelete" icon="trash">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Visitor Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg" name="{{ $visitor->fullName() }}" />
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="first_name" placeholder="{{ __('First Name') }}" class="w-40" />
                            <flux:input wire:model="last_name" placeholder="{{ __('Last Name') }}" class="w-40" />
                        </div>
                        @error('first_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @error('last_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $visitor->fullName() }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($editing)
                            <flux:input type="date" wire:model="visit_date" class="w-40" />
                        @else
                            <span>{{ __('First visited') }} {{ $visitor->visit_date?->format('M d, Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-end gap-2">
                @if($editing)
                    <flux:select wire:model="status" class="w-40">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ str_replace('_', ' ', ucfirst($statusOption->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:badge
                        :color="match($visitor->status->value) {
                            'new' => 'blue',
                            'followed_up' => 'yellow',
                            'returning' => 'green',
                            'converted' => 'purple',
                            'not_interested' => 'zinc',
                            default => 'zinc',
                        }"
                        size="lg"
                    >
                        {{ str_replace('_', ' ', ucfirst($visitor->status->value)) }}
                    </flux:badge>
                @endif
                @if($visitor->is_converted)
                    <flux:badge color="purple" size="sm">
                        <flux:icon icon="check-circle" class="mr-1 size-3" />
                        {{ __('Converted') }}
                    </flux:badge>
                @endif
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Contact Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Contact Information') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="email" wire:model="email" placeholder="{{ __('Email') }}" />
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @else
                            @if($visitor->email)
                                <a href="mailto:{{ $visitor->email }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $visitor->email }}
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
                            @if($visitor->phone)
                                <a href="tel:{{ $visitor->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $visitor->phone }}
                                </a>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Visit Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Visit Information') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('How did you hear about us?') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="how_did_you_hear">
                                <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                                @foreach($this->howDidYouHearOptions as $option)
                                    <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            {{ $visitor->how_did_you_hear ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Attendance Records') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $this->attendanceCount }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Follow-up Assignment -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Follow-up Assignment') }}</flux:heading>
            @if($editing)
                <flux:select wire:model="assigned_to">
                    <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @else
                @if($visitor->assignedMember)
                    <div class="flex items-center gap-3">
                        @if($visitor->assignedMember->photo_url)
                            <img src="{{ $visitor->assignedMember->photo_url }}" alt="{{ $visitor->assignedMember->fullName() }}" class="size-10 rounded-full object-cover" />
                        @else
                            <flux:avatar size="sm" name="{{ $visitor->assignedMember->fullName() }}" />
                        @endif
                        <div>
                            <a href="{{ route('members.show', [$branch, $visitor->assignedMember]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                {{ $visitor->assignedMember->fullName() }}
                            </a>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Assigned for follow-up') }}
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No member assigned for follow-up') }}
                    </p>
                @endif
            @endif
        </div>

        <!-- Conversion Status -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Conversion Status') }}</flux:heading>
            @if($visitor->is_converted && $visitor->convertedMember)
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900">
                        <flux:icon icon="check-circle" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ __('Converted to Member') }}
                        </div>
                        <a href="{{ route('members.show', [$branch, $visitor->convertedMember]) }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                            {{ $visitor->convertedMember->fullName() }}
                        </a>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon icon="clock" class="size-5 text-zinc-400" />
                    </div>
                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ __('Not yet converted') }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Link this visitor to a member when they join') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Notes -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Notes') }}</flux:heading>
        @if($editing)
            <flux:textarea wire:model="notes" placeholder="{{ __('Add notes about this visitor...') }}" rows="4" />
        @else
            @if($visitor->notes)
                <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $visitor->notes }}</p>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notes') }}</p>
            @endif
        @endif
    </div>

    <!-- Convert to Member Modal -->
    <flux:modal wire:model.self="showConvertModal" name="convert-visitor" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Convert to Member') }}</flux:heading>

            <flux:text>
                {{ __('Link :name to an existing member to mark them as converted.', ['name' => $visitor->fullName()]) }}
            </flux:text>

            <form wire:submit="convert" class="space-y-4">
                <flux:select wire:model="convertToMemberId" :label="__('Select Member')" required>
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('convertToMemberId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelConvert" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Convert to Member') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-visitor" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Visitor') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $visitor->fullName()]) }}
            </p>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Visitor') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="visitor-updated" type="success">
        {{ __('Visitor updated successfully.') }}
    </x-toast>

    <x-toast on="visitor-converted" type="success">
        {{ __('Visitor converted to member successfully.') }}
    </x-toast>
</section>
