@extends('errors::layout')

@section('code', '404')
@section('title', 'Page Not Found')
@section('icon-ping-color', 'rgba(59, 130, 246, 0.2)')
@section('icon-color-from', '#3b82f6')
@section('icon-color-to', '#6366f1')
@section('label-color', 'text-blue-600 dark:text-blue-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
    </svg>
@endsection

@section('message')
    Oops! The page you're looking for seems to have wandered off. It might have been moved, deleted, or perhaps never existed in the first place.
@endsection

@section('description')
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 size-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <div>
            <p class="font-medium text-primary">What could have happened?</p>
            <ul class="mt-2 space-y-1 text-muted">
                <li>&bull; The URL might be misspelled</li>
                <li>&bull; The page may have been moved or deleted</li>
                <li>&bull; The link you followed could be outdated</li>
            </ul>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        Go Home
    </a>
    <button onclick="history.back()" class="rounded-full border border-black/10 bg-white/50 px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Go Back
    </button>
@endsection

@section('links')
    <a href="{{ url('/') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Home</a>
    <a href="{{ url('/#features') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Features</a>
    <a href="{{ url('/#pricing') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Pricing</a>
    <a href="{{ url('/#contact') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Contact</a>
@endsection
