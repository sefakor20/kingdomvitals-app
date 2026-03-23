<section class="w-full">
    @include('partials.member-settings-heading')

    <x-settings.member-layout :heading="__('Profile')" :subheading="__('View and update your profile information')" :wide="true">
        <div class="space-y-6">
        {{-- Profile Overview (Read-only) --}}
        <flux:card class="p-6">
            <div class="space-y-6" @if($isProcessingPhoto) wire:poll.1s="checkPhotoStatus" @endif>
                {{-- Profile Header: Photo + Name --}}
                <div class="flex flex-col items-center gap-4">
                    {{-- Photo with pencil overlay --}}
                    <div class="relative">
                        @if($isProcessingPhoto)
                            <div class="flex size-24 items-center justify-center rounded-full bg-zinc-100 ring-2 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                                <flux:icon name="arrow-path" class="size-8 animate-spin text-zinc-400" />
                            </div>
                        @elseif($photo && !$errors->has('photo'))
                            <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Preview') }}" class="size-24 rounded-full object-cover ring-2 ring-green-500" />
                        @elseif($this->member->photo_url)
                            <img src="{{ $this->member->photo_url }}" alt="{{ $this->member->fullName() }}" class="size-24 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700" />
                        @else
                            <flux:avatar size="xl" name="{{ $this->member->fullName() }}" class="size-24" />
                        @endif

                        {{-- Pencil icon overlay --}}
                        @if(!$isProcessingPhoto && !($photo && !$errors->has('photo')))
                            <label class="absolute bottom-0 right-0 flex size-8 cursor-pointer items-center justify-center rounded-full bg-zinc-800 text-white shadow-lg transition-colors hover:bg-zinc-700 dark:bg-zinc-600 dark:hover:bg-zinc-500">
                                <input type="file" wire:model="photo" accept="image/*" class="hidden" />
                                <flux:icon name="pencil" class="size-4" />
                            </label>
                        @endif
                    </div>

                    {{-- Photo action buttons --}}
                    @if(!$isProcessingPhoto)
                        @if($photo && !$errors->has('photo'))
                            <div class="flex gap-2">
                                <flux:button wire:click="uploadPhoto" size="sm" variant="primary">
                                    {{ __('Save') }}
                                </flux:button>
                                <flux:button wire:click="$set('photo', null)" size="sm" variant="ghost">
                                    {{ __('Cancel') }}
                                </flux:button>
                            </div>
                        @elseif($this->member->photo_url)
                            <button type="button" wire:click="removePhoto" wire:confirm="{{ __('Are you sure you want to remove your photo?') }}" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400">
                                {{ __('Remove Photo') }}
                            </button>
                        @endif

                        @error('photo')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    @else
                        <flux:text class="text-sm text-zinc-500">{{ __('Processing...') }}</flux:text>
                    @endif

                    {{-- Full Name --}}
                    <flux:heading size="lg">{{ $this->member->fullName() }}</flux:heading>
                </div>

                {{-- Member Details Grid --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Membership Number') }}</flux:text>
                        <div class="font-medium">{{ $this->member->membership_number }}</div>
                    </div>

                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Email') }}</flux:text>
                        <div class="font-medium">{{ $this->member->email }}</div>
                    </div>

                    @if($this->member->date_of_birth)
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Date of Birth') }}</flux:text>
                            <div class="font-medium">{{ $this->member->date_of_birth->format('M d, Y') }}</div>
                        </div>
                    @endif

                    @if($this->member->gender)
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Gender') }}</flux:text>
                            <div class="font-medium">{{ __($this->member->gender->name) }}</div>
                        </div>
                    @endif

                    @if($this->member->marital_status)
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Marital Status') }}</flux:text>
                            <div class="font-medium">{{ __($this->member->marital_status->name) }}</div>
                        </div>
                    @endif

                    @if($this->member->joined_at)
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Member Since') }}</flux:text>
                            <div class="font-medium">{{ $this->member->joined_at->format('M d, Y') }}</div>
                        </div>
                    @endif

                    @if($this->member->clusters->isNotEmpty())
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Cluster') }}</flux:text>
                            <div class="font-medium">{{ $this->member->clusters->pluck('name')->join(', ') }}</div>
                        </div>
                    @endif
                </div>

                <div class="text-sm text-zinc-500">
                    {{ __('To update your name, email, date of birth, or other personal details, please contact your church administrator.') }}
                </div>
            </div>
        </flux:card>
    </div>
    </x-settings.member-layout>
</section>
