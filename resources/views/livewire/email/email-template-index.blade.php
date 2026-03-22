<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Email Templates') }}</flux:heading>
            <flux:subheading>{{ __('Manage reusable email templates for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('email.index', $branch)" icon="arrow-left" wire:navigate>
                {{ __('Back to Emails') }}
            </flux:button>
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('New Template') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search templates...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->emailTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    @if($this->templates->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="document-text" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No templates found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Create your first template to get started.') }}
                @endif
            </flux:text>
            @if($this->canCreate && !$this->hasActiveFilters)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Create Template') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->templates as $template)
                <div
                    wire:key="template-{{ $template->id }}"
                    class="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <flux:heading size="sm">{{ $template->name }}</flux:heading>
                            <div class="mt-1 flex items-center gap-2">
                                <flux:badge
                                    :color="match($template->type?->value) {
                                        'birthday' => 'pink',
                                        'reminder' => 'yellow',
                                        'announcement' => 'blue',
                                        'newsletter' => 'purple',
                                        'welcome' => 'green',
                                        'follow_up' => 'orange',
                                        'event_reminder' => 'cyan',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $template->type?->value ?? 'custom')) }}
                                </flux:badge>
                                <flux:badge
                                    :color="$template->is_active ? 'green' : 'zinc'"
                                    size="sm"
                                >
                                    {{ $template->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </div>
                        </div>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item wire:click="edit('{{ $template->id }}')" icon="pencil">
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item wire:click="toggleActive('{{ $template->id }}')" icon="{{ $template->is_active ? 'eye-slash' : 'eye' }}">
                                    {{ $template->is_active ? __('Deactivate') : __('Activate') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item wire:click="confirmDelete('{{ $template->id }}')" icon="trash" class="text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    <div class="mb-2">
                        <flux:text class="text-xs font-medium text-zinc-500">{{ __('Subject') }}</flux:text>
                        <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ Str::limit($template->subject, 50) }}</flux:text>
                    </div>

                    <div class="mb-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <flux:text class="line-clamp-3 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-400">
                            {{ strip_tags(Str::markdown($template->body)) }}
                        </flux:text>
                    </div>

                    <div class="flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ strlen(strip_tags($template->body)) }} {{ __('characters') }}</span>
                        <span>{{ __('Updated') }} {{ $template->updated_at?->diffForHumans() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-template" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('New Template') }}</flux:heading>

            <div class="space-y-4">
                <flux:input wire:model="name" :label="__('Template Name')" placeholder="{{ __('e.g., Monthly Newsletter') }}" />
                @error('name')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <flux:select wire:model.live="type" :label="__('Template Type')">
                    @foreach($this->emailTypes as $emailType)
                        <flux:select.option value="{{ $emailType->value }}">
                            {{ ucfirst(str_replace('_', ' ', $emailType->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="subject" :label="__('Subject')" placeholder="{{ __('Email subject line...') }}" />
                @error('subject')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <div x-data="{ charCount: $wire.body?.length || 0, activeTab: 'edit' }">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Body') }}</flux:text>
                        <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <button
                                type="button"
                                @click="activeTab = 'edit'"
                                :class="activeTab === 'edit' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-l-lg transition"
                            >
                                {{ __('Edit') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'preview'"
                                :class="activeTab === 'preview' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-r-lg transition"
                            >
                                {{ __('Preview') }}
                            </button>
                        </div>
                    </div>

                    <!-- Edit Tab -->
                    <div x-show="activeTab === 'edit'">
                        <flux:textarea
                            wire:model.live.debounce.300ms="body"
                            x-on:input="charCount = $event.target.value.length"
                            rows="8"
                            placeholder="{{ __('Write your email using Markdown...') }}"
                            class="font-mono text-sm"
                        />
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            <span x-text="charCount">0</span> {{ __('characters') }}
                        </flux:text>
                        @error('body')
                            <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <!-- Preview Tab -->
                    <div x-show="activeTab === 'preview'">
                        <div class="min-h-[200px] rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            @if($body)
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! $this->bodyPreview !!}
                                </div>
                            @else
                                <flux:text class="text-zinc-400 italic">{{ __('Nothing to preview yet. Start writing in the Edit tab.') }}</flux:text>
                            @endif
                        </div>
                    </div>

                    <!-- Markdown Formatting Guide -->
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Markdown Formatting') }}</flux:text>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            @foreach($this->markdownGuide as $syntax => $description)
                                <span><code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">{{ $syntax }}</code> → {{ $description }}</span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Placeholder Help -->
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Available Placeholders (click to insert):') }}
                        </flux:text>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($this->availablePlaceholders as $placeholder => $description)
                                <button
                                    type="button"
                                    x-on:click="
                                        const textarea = $el.closest('[x-data]').querySelector('textarea');
                                        const start = textarea.selectionStart;
                                        const end = textarea.selectionEnd;
                                        const text = textarea.value;
                                        textarea.value = text.substring(0, start) + '{{ $placeholder }}' + text.substring(end);
                                        textarea.selectionStart = textarea.selectionEnd = start + '{{ $placeholder }}'.length;
                                        textarea.focus();
                                        charCount = textarea.value.length;
                                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                                    "
                                    class="inline-flex items-center rounded-md bg-zinc-200 px-2 py-1 text-xs font-mono text-zinc-700 transition hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                                    title="{{ $description }}"
                                >
                                    {{ $placeholder }}
                                </button>
                            @endforeach
                        </div>
                        <flux:text class="mt-2 text-xs text-zinc-500">
                            {{ __('These placeholders will be replaced with actual member details when the email is sent.') }}
                        </flux:text>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <flux:switch wire:model="is_active" />
                    <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Active') }}</flux:text>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="cancelCreate">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="store">
                    {{ __('Create Template') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-template" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Edit Template') }}</flux:heading>

            <div class="space-y-4">
                <flux:input wire:model="name" :label="__('Template Name')" placeholder="{{ __('e.g., Monthly Newsletter') }}" />
                @error('name')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <flux:select wire:model.live="type" :label="__('Template Type')">
                    @foreach($this->emailTypes as $emailType)
                        <flux:select.option value="{{ $emailType->value }}">
                            {{ ucfirst(str_replace('_', ' ', $emailType->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="subject" :label="__('Subject')" />
                @error('subject')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <div x-data="{ charCount: $wire.body?.length || 0, activeTab: 'edit' }">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Body') }}</flux:text>
                        <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <button
                                type="button"
                                @click="activeTab = 'edit'"
                                :class="activeTab === 'edit' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-l-lg transition"
                            >
                                {{ __('Edit') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'preview'"
                                :class="activeTab === 'preview' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                class="px-3 py-1.5 text-sm font-medium rounded-r-lg transition"
                            >
                                {{ __('Preview') }}
                            </button>
                        </div>
                    </div>

                    <!-- Edit Tab -->
                    <div x-show="activeTab === 'edit'">
                        <flux:textarea
                            wire:model.live.debounce.300ms="body"
                            x-on:input="charCount = $event.target.value.length"
                            rows="8"
                            placeholder="{{ __('Write your email using Markdown...') }}"
                            class="font-mono text-sm"
                        />
                        <flux:text class="mt-1 text-xs text-zinc-500">
                            <span x-text="charCount">0</span> {{ __('characters') }}
                        </flux:text>
                        @error('body')
                            <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <!-- Preview Tab -->
                    <div x-show="activeTab === 'preview'">
                        <div class="min-h-[200px] rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            @if($body)
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! $this->bodyPreview !!}
                                </div>
                            @else
                                <flux:text class="text-zinc-400 italic">{{ __('Nothing to preview yet. Start writing in the Edit tab.') }}</flux:text>
                            @endif
                        </div>
                    </div>

                    <!-- Markdown Formatting Guide -->
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Markdown Formatting') }}</flux:text>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            @foreach($this->markdownGuide as $syntax => $description)
                                <span><code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">{{ $syntax }}</code> → {{ $description }}</span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Placeholder Help -->
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Available Placeholders (click to insert):') }}
                        </flux:text>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($this->availablePlaceholders as $placeholder => $description)
                                <button
                                    type="button"
                                    x-on:click="
                                        const textarea = $el.closest('[x-data]').querySelector('textarea');
                                        const start = textarea.selectionStart;
                                        const end = textarea.selectionEnd;
                                        const text = textarea.value;
                                        textarea.value = text.substring(0, start) + '{{ $placeholder }}' + text.substring(end);
                                        textarea.selectionStart = textarea.selectionEnd = start + '{{ $placeholder }}'.length;
                                        textarea.focus();
                                        charCount = textarea.value.length;
                                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                                    "
                                    class="inline-flex items-center rounded-md bg-zinc-200 px-2 py-1 text-xs font-mono text-zinc-700 transition hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                                    title="{{ $description }}"
                                >
                                    {{ $placeholder }}
                                </button>
                            @endforeach
                        </div>
                        <flux:text class="mt-2 text-xs text-zinc-500">
                            {{ __('These placeholders will be replaced with actual member details when the email is sent.') }}
                        </flux:text>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <flux:switch wire:model="is_active" />
                    <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Active') }}</flux:text>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="cancelEdit">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="update">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-template" class="w-full max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="trash" class="size-6 text-red-600 dark:text-red-400" />
                </div>
                <flux:heading size="lg">{{ __('Delete Template') }}</flux:heading>
            </div>

            @if($deletingTemplate)
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Are you sure you want to delete the template ":name"? This action cannot be undone.', ['name' => $deletingTemplate->name]) }}
                </flux:text>
            @else
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Are you sure you want to delete this template? This action cannot be undone.') }}
                </flux:text>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
