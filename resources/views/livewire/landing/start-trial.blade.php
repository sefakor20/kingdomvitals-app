<section class="relative isolate overflow-hidden pt-28 pb-20 sm:pt-36 lg:pt-40">
    {{-- Glow Effects --}}
    <div class="glow-sphere glow-emerald absolute right-0 top-20 size-[500px] opacity-20"></div>
    <div class="glow-sphere glow-lime absolute -left-40 bottom-0 size-[400px] opacity-15"></div>

    <div class="mx-auto max-w-2xl px-6 lg:px-8">
        @if (! $submitted)
            {{-- Header --}}
            <div class="text-center">
                <div class="mb-6 inline-flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
                    <span class="label-mono text-emerald-600 dark:text-emerald-400">14-day free trial</span>
                </div>
                <h1 class="text-4xl font-light tracking-tight text-primary sm:text-5xl">
                    Start your church on <span class="text-gradient-emerald italic">Kingdom Vitals</span>
                </h1>
                <p class="mt-4 text-lg text-secondary">
                    Set up your workspace in under a minute. No credit card required.
                </p>
            </div>

            {{-- Form Card --}}
            <div class="mt-12 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <form wire:submit="submit" class="space-y-8">
                    {{-- Organization Section --}}
                    <div class="space-y-5">
                        <div class="border-b border-neutral-200 pb-2 dark:border-neutral-800">
                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Organization</h2>
                            <p class="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">Your church and its workspace URL.</p>
                        </div>

                        {{-- Church Name --}}
                        <div>
                            <label for="churchName" class="block text-sm font-medium text-neutral-900 dark:text-white">Organization name</label>
                            <input
                                type="text"
                                wire:model="churchName"
                                id="churchName"
                                class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                placeholder="St. Luke's Anglican Church"
                            >
                            @error('churchName')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Subdomain --}}
                        <div>
                            <label for="subdomain" class="block text-sm font-medium text-neutral-900 dark:text-white">Subdomain</label>
                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">The URL your members will use to log in.</p>
                            <div class="mt-2 flex items-stretch rounded-lg bg-neutral-50 ring-1 ring-inset ring-neutral-200 focus-within:ring-2 focus-within:ring-emerald-500 dark:bg-neutral-800 dark:ring-neutral-700 dark:focus-within:ring-emerald-400">
                                <input
                                    type="text"
                                    wire:model.live.debounce.400ms="subdomain"
                                    id="subdomain"
                                    class="block w-full min-w-0 flex-1 rounded-l-lg border-0 bg-transparent px-4 py-3 text-neutral-900 placeholder:text-neutral-400 focus:ring-0 dark:text-white dark:placeholder:text-neutral-500"
                                    placeholder="stlukes"
                                    autocomplete="off"
                                    autocapitalize="off"
                                    spellcheck="false"
                                >
                                <span class="flex items-center rounded-r-lg bg-neutral-100 px-4 font-mono text-sm text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                                    .{{ $this->parentDomain }}
                                </span>
                            </div>
                            <div class="mt-1 min-h-[1.25rem] text-xs" wire:loading.class="opacity-60" wire:target="subdomain">
                                @switch($subdomainStatus)
                                    @case('available')
                                        <span class="text-emerald-600 dark:text-emerald-400">✓ Available — your church will live at <span class="font-mono">{{ $subdomain }}.{{ $this->parentDomain }}</span></span>
                                        @break
                                    @case('taken')
                                        <span class="text-red-600 dark:text-red-400">That subdomain is already taken</span>
                                        @break
                                    @case('invalid')
                                        <span class="text-red-600 dark:text-red-400">Use 3–30 lowercase letters, numbers, or hyphens</span>
                                        @break
                                    @case('reserved')
                                        <span class="text-red-600 dark:text-red-400">That name is reserved</span>
                                        @break
                                @endswitch
                            </div>
                            @error('subdomain')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Admin Account Section --}}
                    <div class="space-y-5">
                        <div class="border-b border-neutral-200 pb-2 dark:border-neutral-800">
                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Admin account</h2>
                            <p class="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">An activation email will be sent to set up your password.</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Admin Name --}}
                            <div>
                                <label for="adminName" class="block text-sm font-medium text-neutral-900 dark:text-white">Admin name</label>
                                <input
                                    type="text"
                                    wire:model="adminName"
                                    id="adminName"
                                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                    placeholder="Pastor Jane Doe"
                                >
                                @error('adminName')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Admin Email --}}
                            <div>
                                <label for="adminEmail" class="block text-sm font-medium text-neutral-900 dark:text-white">Admin email</label>
                                <input
                                    type="email"
                                    wire:model="adminEmail"
                                    id="adminEmail"
                                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                    placeholder="admin@church.org"
                                    autocomplete="email"
                                >
                                @error('adminEmail')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Contact Section (optional) --}}
                    <div class="space-y-5">
                        <div class="border-b border-neutral-200 pb-2 dark:border-neutral-800">
                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Contact <span class="text-sm font-normal text-neutral-400">(optional)</span></h2>
                            <p class="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">Where your members can reach the church.</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Contact Email --}}
                            <div>
                                <label for="contactEmail" class="block text-sm font-medium text-neutral-900 dark:text-white">Contact email</label>
                                <input
                                    type="email"
                                    wire:model="contactEmail"
                                    id="contactEmail"
                                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                    placeholder="contact@church.org"
                                >
                                @error('contactEmail')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Contact Phone --}}
                            <div>
                                <label for="contactPhone" class="block text-sm font-medium text-neutral-900 dark:text-white">Contact phone</label>
                                <input
                                    type="tel"
                                    wire:model="contactPhone"
                                    id="contactPhone"
                                    class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                    placeholder="+233 50 123 4567"
                                    autocomplete="tel"
                                >
                                @error('contactPhone')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Address --}}
                        <div>
                            <label for="address" class="block text-sm font-medium text-neutral-900 dark:text-white">Address</label>
                            <textarea
                                wire:model="address"
                                id="address"
                                rows="2"
                                class="mt-2 block w-full rounded-lg border-0 bg-neutral-50 px-4 py-3 text-neutral-900 ring-1 ring-inset ring-neutral-200 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-emerald-500 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700 dark:placeholder:text-neutral-500 dark:focus:ring-emerald-400"
                                placeholder="123 Main Street, Accra, Ghana"
                            ></textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="btn-neon w-full rounded-full px-4 py-4 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                        >
                            <span wire:loading.remove wire:target="submit">Start my 14-day free trial</span>
                            <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                                <svg class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating your church…
                            </span>
                        </button>
                        <p class="mt-3 text-center text-xs text-muted">No credit card required. Cancel anytime.</p>
                    </div>
                </form>
            </div>
        @else
            {{-- Success --}}
            <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500/10">
                    <svg class="size-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-light tracking-tight text-primary">Check your inbox</h2>
                <p class="mt-3 text-secondary">
                    We sent an activation link to <strong class="text-primary">{{ $adminEmail }}</strong>.
                    Click it to set your password and unlock
                    <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $createdDomain }}</span>.
                </p>
                <p class="mt-2 text-xs text-muted">The link is valid for 60 minutes. Check your spam folder if you don't see it.</p>
                <a href="{{ route('home') }}" class="mt-8 inline-block text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                    ← Back to home
                </a>
            </div>
        @endif
    </div>
</section>
