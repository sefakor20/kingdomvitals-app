<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
        <title>{{ config('app.name') }} - Church Management Made Simple</title>
        <meta name="description" content="The all-in-one platform to manage your membership, giving, attendance, volunteers, and more — so you can focus on ministry.">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name') }} - Church Management Made Simple">
        <meta property="og:description" content="The all-in-one platform to manage your membership, giving, attendance, volunteers, and more — so you can focus on ministry.">
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">

        {{-- Twitter Card Meta Tags --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ config('app.name') }} - Church Management Made Simple">
        <meta name="twitter:description" content="The all-in-one platform to manage your membership, giving, attendance, volunteers, and more.">
        <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

        {{-- Additional SEO --}}
        <meta name="robots" content="index, follow">
        <meta name="author" content="Kingdom Vitals">
        <link rel="canonical" href="{{ url('/') }}">

        {{-- JSON-LD Structured Data --}}
        @php
            $jsonLd = [
                '@context' => 'https://schema.org',
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
                'provider' => [
                    '@type' => 'Organization',
                    'name' => 'Kingdom Vitals',
                    'url' => url('/'),
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
