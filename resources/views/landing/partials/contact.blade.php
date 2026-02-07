<section id="contact" class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto grid max-w-5xl gap-16 lg:grid-cols-2">
            {{-- Contact Info --}}
            <div>
                <p class="text-sm font-medium uppercase tracking-widest text-purple-600 dark:text-purple-400">Contact Us</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                    Get in touch
                </h2>
                <p class="mt-6 text-lg leading-8 text-neutral-600 dark:text-neutral-400">
                    Have questions about Kingdom Vitals? We'd love to hear from you. Fill out the form and our team will get back to you within 24 hours.
                </p>

                <dl class="mt-10 space-y-6">
                    {{-- Email --}}
                    <div class="flex gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                            <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-900 dark:text-white">Email</dt>
                            <dd class="mt-1 text-neutral-600 dark:text-neutral-400">hello@kingdomvitals.app</dd>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="flex gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                            <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-900 dark:text-white">Phone</dt>
                            <dd class="mt-1 text-neutral-600 dark:text-neutral-400">+233 50 922 8314/+233 54 882 8183</dd>
                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="flex gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                            <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-900 dark:text-white">Location</dt>
                            <dd class="mt-1 text-neutral-600 dark:text-neutral-400">Accra, Ghana</dd>
                        </div>
                    </div>
                </dl>
            </div>

            {{-- Contact Form --}}
            <livewire:landing.contact-form />
        </div>
    </div>
</section>
