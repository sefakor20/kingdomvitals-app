<div>
    {{-- ============================================================
         FULL-HEIGHT DOCKED PANEL
         ============================================================ --}}
    <div
        x-show="$store.supportChat.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        class="fixed inset-y-0 right-0 z-40 flex h-screen w-full flex-col border-l border-zinc-200 bg-white sm:w-96 dark:border-zinc-700 dark:bg-zinc-900"
        x-cloak
        x-data
    >
        {{-- Panel Header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <div class="flex items-center gap-2.5">
                <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#009866]">
                    <flux:icon icon="sparkles" class="size-4 text-white" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('AI Assistant') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('KingdomVitals Support') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                @if(count($messages) > 0)
                    <flux:button
                        wire:click="clearConversation"
                        variant="ghost"
                        size="sm"
                        icon="arrow-path"
                        title="{{ __('New conversation') }}"
                    />
                @endif
                <button
                    type="button"
                    @click="$store.supportChat.close()"
                    class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                    title="{{ __('Close') }}"
                >
                    <flux:icon icon="x-mark" class="size-4" />
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div
            class="flex flex-1 flex-col gap-3 overflow-y-auto px-4 py-4"
            x-ref="messageList"
            x-effect="
                $wire.messages;
                $wire.streamingContent;
                $nextTick(() => { $el.scrollTop = $el.scrollHeight; });
            "
        >
            @if(count($messages) === 0 && !$isStreaming)
                <div class="flex flex-col items-center justify-center gap-4 py-10 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-900/20">
                        <flux:icon icon="sparkles" class="size-7 text-[#009866] dark:text-emerald-400" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('How can I help you?') }}</p>
                        <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                            {{ __('Ask me anything about KingdomVitals — members, giving, events, settings, or your subscription.') }}
                        </p>
                    </div>
                    <div class="w-full space-y-2 pt-1">
                        @foreach([
                            'How do I add a new member?',
                            'How does giving tracking work?',
                            'How do I set up attendance check-in?',
                            'What subscription plans are available?',
                        ] as $suggestion)
                            <button
                                type="button"
                                wire:click="$set('newMessage', '{{ $suggestion }}')"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2.5 text-left text-xs text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:border-emerald-600 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400"
                            >
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach($this->renderedMessages as $index => $message)
                @if($message['role'] === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-[#009866] px-3.5 py-2.5 text-sm leading-relaxed text-white shadow-sm">
                            {{ $message['content'] }}
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                            <flux:icon icon="sparkles" class="size-3.5 text-[#009866] dark:text-emerald-400" />
                        </div>
                        <div class="flex flex-1 flex-col gap-1.5">
                            <div class="prose-chat max-w-full rounded-2xl rounded-bl-sm bg-zinc-100 px-3.5 py-2.5 text-sm leading-relaxed text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-200">
                                {!! $message['content'] !!}
                            </div>
                            {{-- Feedback thumbs --}}
                            @php $existingRating = $this->getFeedbackFor($index); @endphp
                            <div class="flex items-center gap-1 pl-1">
                                <button
                                    type="button"
                                    wire:click="submitFeedback({{ $index }}, 'up')"
                                    title="{{ __('Helpful') }}"
                                    class="flex size-6 items-center justify-center rounded-md transition {{ $existingRating === 'up' ? 'text-[#009866]' : 'text-zinc-300 hover:text-[#009866] dark:text-zinc-600 dark:hover:text-emerald-400' }}"
                                >
                                    <flux:icon icon="hand-thumb-up" class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="submitFeedback({{ $index }}, 'down')"
                                    title="{{ __('Not helpful') }}"
                                    class="flex size-6 items-center justify-center rounded-md transition {{ $existingRating === 'down' ? 'text-red-500' : 'text-zinc-300 hover:text-red-400 dark:text-zinc-600 dark:hover:text-red-400' }}"
                                >
                                    <flux:icon icon="hand-thumb-down" class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Thinking indicator: shows immediately on Livewire request before streaming begins --}}
            <div wire:loading wire:target="sendMessage" class="flex items-start gap-2.5">
                <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                    <flux:icon icon="sparkles" class="size-3.5 animate-pulse text-[#009866] dark:text-emerald-400" />
                </div>
                <div class="py-2 text-sm text-zinc-400 dark:text-zinc-500">
                    <span class="animate-pulse">{{ __('Thinking…') }}</span>
                </div>
            </div>

            {{-- Streaming bubble: shows streamed content as it arrives --}}
            @if($isStreaming && $streamingContent)
                <div class="flex items-start gap-2.5">
                    <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <flux:icon icon="sparkles" class="size-3.5 text-[#009866] dark:text-emerald-400" />
                    </div>
                    <div class="prose-chat max-w-[85%] rounded-2xl rounded-bl-sm bg-zinc-100 px-3.5 py-2.5 text-sm leading-relaxed text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-200">
                        {!! $this->renderMarkdown($streamingContent) !!}
                    </div>
                </div>
            @endif
        </div>

        @if($errorMessage)
            <div class="shrink-0 border-t border-red-200 bg-red-50 px-4 py-2 dark:border-red-900 dark:bg-red-950">
                <p class="text-xs text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
            </div>
        @endif

        {{-- Input --}}
        <div class="shrink-0 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                <textarea
                    wire:model="newMessage"
                    placeholder="{{ __('Ask a question...') }}"
                    rows="1"
                    class="min-h-[2.5rem] flex-1 resize-none rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 outline-none transition focus:border-[#009866] focus:ring-2 focus:ring-emerald-100 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-emerald-500 dark:focus:ring-emerald-900/30"
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px';"
                    :disabled="$wire.isStreaming"
                ></textarea>
                <button
                    type="submit"
                    :disabled="$wire.isStreaming"
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#009866] text-white transition hover:bg-[#007a52] disabled:opacity-50"
                >
                    <flux:icon icon="paper-airplane" class="size-4" />
                </button>
            </form>
            <p class="mt-1.5 text-center text-[11px] text-zinc-400 dark:text-zinc-600">
                {{ __('Enter to send · Shift+Enter for new line') }}
            </p>
        </div>
    </div>

    {{-- Backdrop (mobile) --}}
    <div
        x-show="$store.supportChat.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.supportChat.close()"
        class="fixed inset-0 z-30 bg-black/20 sm:hidden"
        x-cloak
    ></div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        if (!Alpine.store('supportChat')) {
            Alpine.store('supportChat', {
                open: false,
                toggle() { this.open = !this.open; },
                close() { this.open = false; },
            });
        }
    });
</script>

<style>
    .prose-chat strong { font-weight: 600; }
    .prose-chat em { font-style: italic; }
    .prose-chat ol { list-style-type: decimal; padding-left: 1.25rem; margin: 0.375rem 0; }
    .prose-chat ul { list-style-type: disc; padding-left: 1.25rem; margin: 0.375rem 0; }
    .prose-chat li { margin: 0.15rem 0; }
    .prose-chat hr { margin: 0.5rem 0; border-color: rgb(228 228 231); }
    .dark .prose-chat hr { border-color: rgb(63 63 70); }
    .prose-chat code { background: rgb(228 228 231); border-radius: 0.25rem; padding: 0.1rem 0.3rem; font-size: 0.75rem; font-family: ui-monospace, monospace; }
    .dark .prose-chat code { background: rgb(63 63 70); }
</style>
