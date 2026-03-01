<section id="testimonials" class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center scroll-reveal">
            <p class="label-mono text-emerald-600 dark:text-emerald-400">Testimonials</p>
            <h2 class="mt-4 text-4xl font-light tracking-tighter text-primary sm:text-5xl">
                Loved by churches everywhere
            </h2>
            <p class="mt-6 text-lg leading-8 text-secondary">
                See what pastors and church administrators are saying about Kingdom Vitals.
            </p>
        </div>

        {{-- Testimonials grid --}}
        <div class="mx-auto mt-16 grid max-w-5xl gap-6 sm:mt-20 md:grid-cols-3">
            {{-- Testimonial 1 --}}
            <div class="glass-card card-lift relative p-8 hover:border-emerald-500/40 scroll-reveal reveal-delay-1">
                {{-- Quote icon --}}
                <svg class="absolute right-6 top-6 size-8 text-emerald-500/20" fill="currentColor" viewBox="0 0 32 32">
                    <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                </svg>

                <div class="flex items-center gap-1">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="size-5 text-lime-accent" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                        </svg>
                    @endfor
                </div>

                <blockquote class="mt-6 text-secondary">
                    "Kingdom Vitals has transformed how we manage our church. The attendance tracking and online giving features have saved us countless hours every week."
                </blockquote>

                <div class="mt-6 flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-lime-500 text-lg font-semibold text-obsidian-base">
                        JA
                    </div>
                    <div>
                        <div class="font-semibold text-primary">Pastor John Acheampong</div>
                        <div class="text-sm text-muted">Grace Community Church</div>
                    </div>
                </div>
            </div>

            {{-- Testimonial 2 --}}
            <div class="glass-card card-lift relative p-8 hover:border-emerald-500/40 scroll-reveal reveal-delay-2">
                <svg class="absolute right-6 top-6 size-8 text-emerald-500/20" fill="currentColor" viewBox="0 0 32 32">
                    <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                </svg>

                <div class="flex items-center gap-1">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="size-5 text-lime-accent" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                        </svg>
                    @endfor
                </div>

                <blockquote class="mt-6 text-secondary">
                    "The visitor follow-up feature has helped us connect with newcomers more effectively. Our conversion rate has improved significantly since we started using the platform."
                </blockquote>

                <div class="mt-6 flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-lime-500 to-emerald-500 text-lg font-semibold text-obsidian-base">
                        SA
                    </div>
                    <div>
                        <div class="font-semibold text-primary">Sarah Adjei</div>
                        <div class="text-sm text-muted">Church Administrator, Victory Chapel</div>
                    </div>
                </div>
            </div>

            {{-- Testimonial 3 --}}
            <div class="glass-card card-lift relative p-8 hover:border-emerald-500/40 scroll-reveal reveal-delay-3">
                <svg class="absolute right-6 top-6 size-8 text-emerald-500/20" fill="currentColor" viewBox="0 0 32 32">
                    <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                </svg>

                <div class="flex items-center gap-1">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="size-5 text-lime-accent" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                        </svg>
                    @endfor
                </div>

                <blockquote class="mt-6 text-secondary">
                    "Managing our three branches used to be a nightmare. Now with Kingdom Vitals, we have a unified view of all our locations and can make data-driven decisions."
                </blockquote>

                <div class="mt-6 flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-lg font-semibold text-white">
                        KO
                    </div>
                    <div>
                        <div class="font-semibold text-primary">Rev. Kwame Owusu</div>
                        <div class="text-sm text-muted">New Life Assembly</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
