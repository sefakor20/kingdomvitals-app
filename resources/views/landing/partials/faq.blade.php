<section id="faq" class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-medium uppercase tracking-widest text-purple-600 dark:text-purple-400">FAQ</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                Frequently asked questions
            </h2>
            <p class="mt-6 text-lg leading-8 text-neutral-600 dark:text-neutral-400">
                Everything you need to know about Kingdom Vitals.
            </p>
        </div>

        {{-- FAQ accordion --}}
        <div class="mx-auto mt-16 max-w-3xl divide-y divide-neutral-200 dark:divide-neutral-800" x-data="{ openItem: null }">
            {{-- FAQ Item 1 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 1 ? null : 1"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">How does online giving work?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 1 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 1"
                    x-collapse
                >
                    Members can give online through a secure payment portal powered by Paystack. They can make one-time donations or set up recurring giving for tithes, offerings, building funds, and more. All transactions are tracked automatically and linked to member profiles for easy reporting.
                </div>
            </div>

            {{-- FAQ Item 2 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 2 ? null : 2"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">Can I manage multiple branches?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 2 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 2"
                    x-collapse
                >
                    Yes! Kingdom Vitals is built for multi-site churches. You can manage multiple branches from a single account, with each branch having its own members, attendance tracking, and financial records. You can view data by branch or across your entire organization.
                </div>
            </div>

            {{-- FAQ Item 3 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 3 ? null : 3"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">Is my data secure?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 3 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 3"
                    x-collapse
                >
                    Absolutely. We use industry-standard encryption for all data in transit and at rest. Each church's data is isolated in separate databases, ensuring complete privacy. We also offer two-factor authentication for added account security, and regular backups protect against data loss.
                </div>
            </div>

            {{-- FAQ Item 4 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 4 ? null : 4"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">Can I import existing member data?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 4 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 4"
                    x-collapse
                >
                    Yes, you can import member data from spreadsheets (CSV/Excel). Our import wizard guides you through mapping your existing data fields to Kingdom Vitals. If you need help with a complex migration, our support team is available to assist.
                </div>
            </div>

            {{-- FAQ Item 5 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 5 ? null : 5"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">What payment methods are supported?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 5 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 5"
                    x-collapse
                >
                    We support a wide range of payment methods through our Paystack integration, including credit/debit cards (Visa, Mastercard), mobile money (MTN, Vodafone, AirtelTigo), and bank transfers. This ensures your members can give using their preferred method.
                </div>
            </div>

            {{-- FAQ Item 6 --}}
            <div class="py-6">
                <button
                    type="button"
                    class="flex w-full items-start justify-between text-left"
                    @click="openItem = openItem === 6 ? null : 6"
                >
                    <span class="text-base font-semibold text-neutral-900 dark:text-white">Do you offer a free trial?</span>
                    <svg
                        class="size-6 shrink-0 text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': openItem === 6 }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    class="mt-4 text-neutral-600 dark:text-neutral-400"
                    x-show="openItem === 6"
                    x-collapse
                >
                    Yes! We offer a 14-day free trial with full access to all features. No credit card required to start. This gives you plenty of time to explore the platform and see how it can benefit your church before committing to a plan.
                </div>
            </div>
        </div>
    </div>
</section>
