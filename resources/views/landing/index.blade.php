<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
        <title>{{ config('app.name') }} - AI-Powered Church Management</title>
        <meta name="description" content="AI-powered church management platform. Predict trends, identify at-risk members, and get actionable insights while managing membership, giving, and attendance.">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name') }} - AI-Powered Church Management">
        <meta property="og:description" content="AI-powered church management platform. Predict trends, identify at-risk members, and get actionable insights while managing your ministry.">
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">

        {{-- Twitter Card Meta Tags --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ config('app.name') }} - AI-Powered Church Management">
        <meta name="twitter:description" content="AI-powered church management with predictive insights, smart alerts, and actionable recommendations.">
        <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

        {{-- Additional SEO --}}
        <meta name="robots" content="index, follow">
        <meta name="author" content="Kingdom Vitals">
        <link rel="canonical" href="{{ url('/') }}">

        {{-- JSON-LD Structured Data --}}
        @php
            // SoftwareApplication schema
            $softwareApp = [
                '@type' => 'SoftwareApplication',
                'name' => config('app.name'),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => 'Church management software for membership, giving, attendance, and ministry operations.',
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'GHS',
                    'lowPrice' => '0',
                    'offerCount' => (string) count($plans),
                ],
            ];

            // Organization schema with contact info
            $organization = [
                '@type' => 'Organization',
                'name' => 'Kingdom Vitals',
                'url' => url('/'),
                'logo' => asset('images/logo.png'),
                'description' => 'Church management software helping churches in Ghana and beyond manage their membership, giving, and ministry operations.',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => 'GH',
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+233509228314',
                    'contactType' => 'customer service',
                    'availableLanguage' => 'English',
                ],
                'sameAs' => [
                    'https://wa.me/233509228314',
                ],
            ];

            // FAQPage schema for rich snippets
            $faqPage = [
                '@type' => 'FAQPage',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name' => 'How does online giving work?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Members can give online through a secure payment portal powered by Paystack. They can make one-time donations or set up recurring giving for tithes, offerings, building funds, and more. All transactions are tracked automatically and linked to member profiles for easy reporting.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Can I manage multiple branches?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Yes! Kingdom Vitals is built for multi-site churches. You can manage multiple branches from a single account, with each branch having its own members, attendance tracking, and financial records. You can view data by branch or across your entire organization.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Is my data secure?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Absolutely. We use industry-standard encryption for all data in transit and at rest. Each church\'s data is isolated in separate databases, ensuring complete privacy. We also offer two-factor authentication for added account security, and regular backups protect against data loss.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Can I import existing member data?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Yes, you can import member data from spreadsheets (CSV/Excel). Our import wizard guides you through mapping your existing data fields to Kingdom Vitals. If you need help with a complex migration, our support team is available to assist.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'What payment methods are supported?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'We support a wide range of payment methods through our Paystack integration, including credit/debit cards (Visa, Mastercard), mobile money (MTN, Vodafone, AirtelTigo), and bank transfers. This ensures your members can give using their preferred method.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Do you offer a free trial?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Yes! We offer a 14-day free trial with full access to all features. No credit card required to start. This gives you plenty of time to explore the platform and see how it can benefit your church before committing to a plan.',
                        ],
                    ],
                ],
            ];

            // Combine all schemas using @graph
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $softwareApp,
                    $organization,
                    $faqPage,
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        {{-- Skip to content link for keyboard/screen reader users --}}
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-purple-600 focus:px-4 focus:py-2 focus:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
            {{ __('Skip to main content') }}
        </a>

        {{-- Navigation --}}
        @include('landing.partials.navigation')

        {{-- Main Content --}}
        <main id="main-content">
            {{-- Hero Section --}}
            @include('landing.partials.hero')

        {{-- Social Proof --}}
        @include('landing.partials.social-proof')

        {{-- Features Section --}}
        @include('landing.partials.features')

        {{-- AI Features Section --}}
        @include('landing.partials.ai-features')

        {{-- How It Works --}}
        @include('landing.partials.how-it-works')

        {{-- Pricing Section --}}
        @include('landing.partials.pricing', ['plans' => $plans])

        {{-- Testimonials --}}
        @include('landing.partials.testimonials')

        {{-- FAQ Section --}}
        @include('landing.partials.faq')

        {{-- Contact Section --}}
        @include('landing.partials.contact')

        {{-- Final CTA --}}
        @include('landing.partials.cta')

        {{-- Footer --}}
        @include('landing.partials.footer')
        </main>

        {{-- Floating WhatsApp Button --}}
        @include('landing.partials.whatsapp-button')

        {{-- Cookie Consent Banner --}}
        @include('landing.partials.cookie-consent')

        @fluxScripts
    </body>
</html>
