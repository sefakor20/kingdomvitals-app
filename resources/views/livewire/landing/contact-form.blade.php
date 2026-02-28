<div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
    {{-- Success Message --}}
    @if ($submitted)
        <div class="rounded-lg bg-green-50 p-6 text-center dark:bg-green-900/20">
            <svg class="mx-auto size-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-green-800 dark:text-green-200">Message Sent!</h3>
            <p class="mt-2 text-green-700 dark:text-green-300">Thank you for reaching out. We'll get back to you within 24 hours.</p>
            <button
                type="button"
                wire:click="$set('submitted', false)"
                class="mt-4 text-sm font-medium text-green-600 hover:text-green-500 dark:text-green-400"
            >
                Send another message
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-6">
            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-neutral-900 dark:text-white">Name</label>
                <input
                    type="text"
                    wire:model="name"
                    id="name"
                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-purple-500"
                    placeholder="Your name"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-neutral-900 dark:text-white">Email</label>
                <input
                    type="email"
                    wire:model="email"
                    id="email"
                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-purple-500"
                    placeholder="you@church.org"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Church Name --}}
            <div>
                <label for="church" class="block text-sm font-medium text-neutral-900 dark:text-white">Church Name <span class="text-neutral-400">(optional)</span></label>
                <input
                    type="text"
                    wire:model="church"
                    id="church"
                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-purple-500"
                    placeholder="Your church name"
                >
                @error('church')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Church Size --}}
            <div>
                <label for="size" class="block text-sm font-medium text-neutral-900 dark:text-white">Church Size <span class="text-neutral-400">(optional)</span></label>
                <select
                    wire:model="size"
                    id="size"
                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 focus:ring-2 focus:ring-inset focus:ring-purple-600 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:focus:ring-purple-500"
                >
                    <option value="">Select church size</option>
                    <option value="1-50">1-50 members</option>
                    <option value="51-100">51-100 members</option>
                    <option value="101-250">101-250 members</option>
                    <option value="251-500">251-500 members</option>
                    <option value="501-1000">501-1000 members</option>
                    <option value="1000+">1000+ members</option>
                </select>
                @error('size')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Message --}}
            <div>
                <label for="message" class="block text-sm font-medium text-neutral-900 dark:text-white">Message</label>
                <textarea
                    wire:model="message"
                    id="message"
                    rows="4"
                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-purple-500"
                    placeholder="Tell us about your church and how we can help..."
                ></textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div>
                <button
                    type="submit"
                    class="btn-neon w-full rounded-full px-4 py-3 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Send Message</span>
                    <span wire:loading class="flex items-center justify-center gap-2">
                        <svg class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
