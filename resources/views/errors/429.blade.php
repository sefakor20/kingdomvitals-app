@extends('errors::layout')

@section('code', '429')
@section('title', 'Too Many Requests')
@section('icon-ping-color', 'rgba(234, 179, 8, 0.2)')
@section('icon-color-from', '#eab308')
@section('icon-color-to', '#f97316')
@section('label-color', 'text-yellow-600 dark:text-yellow-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
    </svg>
@endsection

@section('message')
    Whoa there! You're making requests a bit too quickly. Please slow down and try again in a moment. This helps us keep the service running smoothly for everyone.
@endsection

@section('description')
    <div class="space-y-4">
        {{-- Cooldown indicator --}}
        <div>
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-medium text-primary">Rate Limit Cooldown</span>
                <span class="text-yellow-600 dark:text-yellow-400">Please wait...</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-black/5 dark:bg-white/5">
                <div class="h-full w-full animate-pulse rounded-full bg-gradient-to-r from-yellow-500 to-orange-500"></div>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <svg class="mt-0.5 size-5 flex-shrink-0 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div>
                <p class="font-medium text-primary">Why did this happen?</p>
                <ul class="mt-2 space-y-1 text-muted">
                    <li>&bull; Too many requests in a short time</li>
                    <li>&bull; Rate limiting protects our servers</li>
                    <li>&bull; Wait a minute and try again</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <button onclick="setTimeout(() => location.reload(), 1000)" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Try Again
    </button>
    <a href="{{ url('/') }}" class="rounded-full border border-black/10 bg-white/50 px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        Go Home
    </a>
@endsection
