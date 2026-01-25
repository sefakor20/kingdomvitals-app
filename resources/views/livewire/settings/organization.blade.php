<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Organization')" :subheading="__('Customize your organization\'s branding')">
        <div class="my-6 w-full space-y-6">
            <!-- Organization Logo -->
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Organization Logo') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ __('Upload a custom logo for your organization. This will appear in the sidebar and other areas.') }}</flux:text>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <!-- Current Logo Preview -->
                        <div class="shrink-0">
                            <div class="relative h-32 w-32 overflow-hidden rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700">
                                @if($logo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    <img
                                        src="{{ $logo->temporaryUrl() }}"
                                        alt="{{ __('New logo preview') }}"
                                        class="h-full w-full object-contain p-2"
                                    />
                                @elseif($existingLogoUrl)
                                    <img
                                        src="{{ $existingLogoUrl }}"
                                        alt="{{ __('Current organization logo') }}"
                                        class="h-full w-full object-contain p-2"
                                    />
                                @else
                                    <div class="flex h-full w-full flex-col items-center justify-center text-zinc-400">
                                        <flux:icon.photo class="size-8" />
                                        <span class="mt-1 text-xs">{{ __('No logo') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Upload Controls -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <flux:field>
                                    <flux:label>{{ __('Upload New Logo') }}</flux:label>
                                    <input
                                        type="file"
                                        wire:model="logo"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-zinc-400 dark:file:bg-indigo-900/50 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900"
                                    />
                                    <flux:description>{{ __('PNG, JPG, or WebP. Minimum 256x256 pixels. Maximum 2MB.') }}</flux:description>
                                    <flux:error name="logo" />
                                </flux:field>
                            </div>

                            <div class="flex gap-2">
                                @if($logo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    <flux:button wire:click="saveLogo" variant="primary" size="sm">
                                        {{ __('Save Logo') }}
                                    </flux:button>
                                    <flux:button wire:click="$set('logo', null)" variant="ghost" size="sm">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                @endif

                                @if($existingLogoUrl && !$logo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    <flux:button wire:click="removeLogo" variant="ghost" size="sm" class="text-red-600 hover:text-red-700">
                                        {{ __('Remove Logo') }}
                                    </flux:button>
                                @endif
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                {{ __('If no logo is set, the platform default logo will be used.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <x-toast on="logo-saved" type="success">
            {{ __('Logo uploaded successfully.') }}
        </x-toast>
        <x-toast on="logo-removed" type="success">
            {{ __('Logo removed successfully.') }}
        </x-toast>
    </x-settings.layout>
</section>
