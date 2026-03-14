<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/50">
                <flux:icon icon="chat-bubble-left-right" class="size-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl">{{ __('Chatbot Monitor') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Monitor SMS and WhatsApp chatbot conversations for :branch', ['branch' => $branch->name]) }}
                </flux:text>
            </div>
        </div>
        <flux:badge color="purple">{{ __('AI-Powered') }}</flux:badge>
    </div>

    @if(!$this->featureEnabled)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon icon="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                <div>
                    <flux:heading size="base">{{ __('Feature Disabled') }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('Chatbot feature is currently disabled. Enable it in AI settings.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    @else
        {{-- Stats --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Conversations') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ number_format($this->stats['total_conversations']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Today') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-green-600 dark:text-green-400">{{ number_format($this->stats['active_today']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Messages') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ number_format($this->stats['total_messages']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Inbound') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-blue-600 dark:text-blue-400">{{ number_format($this->stats['inbound_messages']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('SMS') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ number_format($this->stats['sms_conversations']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('WhatsApp') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ number_format($this->stats['whatsapp_conversations']) }}</flux:heading>
            </div>
        </div>

        {{-- Intent Distribution --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-4">{{ __('Intent Distribution') }}</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($this->intentDistribution as $intent => $count)
                    @if($count > 0)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                            <flux:text class="text-sm capitalize">{{ str_replace('_', ' ', $intent) }}</flux:text>
                            <flux:badge color="zinc">{{ $count }}</flux:badge>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Conversations List --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-4">
                        <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search...') }}" size="sm" class="w-40" />

                        <flux:select wire:model.live="channelFilter" size="sm" class="w-32" placeholder="{{ __('All Channels') }}">
                            <option value="">{{ __('All Channels') }}</option>
                            @foreach($this->availableChannels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>

                        @if($channelFilter !== '' || $search !== '')
                            <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                                <flux:icon icon="x-mark" class="size-4" />
                            </flux:button>
                        @endif
                    </div>
                </div>

                <div class="max-h-[600px] divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700">
                    @forelse($this->conversations as $conversation)
                        <div
                            wire:click="selectConversation('{{ $conversation->id }}')"
                            class="cursor-pointer p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $selectedConversationId === $conversation->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 items-center justify-center rounded-full {{ $conversation->channel === \App\Enums\ChatbotChannel::Sms ? 'bg-green-100 dark:bg-green-900/50' : 'bg-blue-100 dark:bg-blue-900/50' }}">
                                        <flux:icon icon="{{ $conversation->channel === \App\Enums\ChatbotChannel::Sms ? 'device-phone-mobile' : 'chat-bubble-oval-left' }}"
                                                   class="size-5 {{ $conversation->channel === \App\Enums\ChatbotChannel::Sms ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}" />
                                    </div>
                                    <div>
                                        @if($conversation->member)
                                            <flux:text class="font-medium">{{ $conversation->member->fullName() }}</flux:text>
                                        @else
                                            <flux:text class="font-medium">{{ __('Unknown') }}</flux:text>
                                        @endif
                                        <flux:text class="text-sm text-zinc-500">{{ $conversation->phone_number }}</flux:text>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <flux:badge size="sm" :color="$conversation->channel === \App\Enums\ChatbotChannel::Sms ? 'green' : 'blue'">
                                        {{ $conversation->channel->label() }}
                                    </flux:badge>
                                    <flux:text class="mt-1 block text-xs text-zinc-400">
                                        {{ $conversation->last_message_at?->diffForHumans() }}
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <flux:icon icon="chat-bubble-left-right" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="mt-2 text-zinc-500">{{ __('No conversations yet.') }}</flux:text>
                        </div>
                    @endforelse
                </div>

                @if($this->conversations->hasPages())
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        {{ $this->conversations->links() }}
                    </div>
                @endif
            </div>

            {{-- Conversation Detail --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                @if($this->selectedConversation)
                    <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <flux:heading size="base">
                                @if($this->selectedConversation->member)
                                    {{ $this->selectedConversation->member->fullName() }}
                                @else
                                    {{ $this->selectedConversation->phone_number }}
                                @endif
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-500">
                                {{ $this->selectedConversation->messages->count() }} {{ __('messages') }}
                            </flux:text>
                        </div>
                        <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                            <flux:icon icon="x-mark" class="size-4" />
                        </flux:button>
                    </div>

                    <div class="max-h-[500px] space-y-4 overflow-y-auto p-4">
                        @foreach($this->selectedConversation->messages as $message)
                            <div class="flex {{ $message->is_inbound ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[80%] rounded-lg px-4 py-2 {{ $message->is_inbound ? 'bg-zinc-100 dark:bg-zinc-800' : 'bg-blue-500 text-white' }}">
                                    <flux:text class="{{ $message->is_inbound ? '' : 'text-white' }}">
                                        {{ $message->content }}
                                    </flux:text>
                                    <div class="mt-1 flex items-center gap-2 {{ $message->is_inbound ? 'text-zinc-400' : 'text-blue-200' }}">
                                        <span class="text-xs">{{ $message->created_at->format('g:i A') }}</span>
                                        @if($message->intent && $message->is_inbound)
                                            <flux:badge size="sm" color="{{ $message->is_inbound ? 'zinc' : 'blue' }}">
                                                {{ str_replace('_', ' ', $message->intent) }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex h-full min-h-[400px] flex-col items-center justify-center p-8">
                        <flux:icon icon="chat-bubble-left-ellipsis" class="size-16 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="mt-4 text-zinc-500">{{ __('Select a conversation to view messages') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
