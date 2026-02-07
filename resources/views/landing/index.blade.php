<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
        <title>{{ config('app.name') }} - Church Management Made Simple</title>
        <meta name="description" content="The all-in-one platform to manage your membership, giving, attendance, volunteers, and more — so you can focus on ministry.">
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        {{-- Navigation --}}
        @include('landing.partials.navigation')

        {{-- Hero Section --}}
        @include('landing.partials.hero')

        {{-- Social Proof --}}
        @include('landing.partials.social-proof')

        {{-- Features Section --}}
        @include('landing.partials.features')

        {{-- How It Works --}}
        @include('landing.partials.how-it-works')

        {{-- Pricing Section --}}
        @include('landing.partials.pricing')

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

        @fluxScripts
    </body>
</html>
