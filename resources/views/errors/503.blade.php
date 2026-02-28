@extends('errors::layout')

@section('code', '503')
@section('title', 'Under Maintenance')
@section('icon-ping-color', 'rgba(245, 158, 11, 0.2)')
@section('icon-color-from', '#f59e0b')
@section('icon-color-to', '#eab308')
@section('label-color', 'text-amber-600 dark:text-amber-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z" />
    </svg>
@endsection

@section('message')
    We're performing scheduled maintenance to improve your experience. We'll be back online shortly. Thank you for your patience!
@endsection

@section('description')
    <div class="space-y-4">
        {{-- Progress indicator --}}
        <div>
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-medium text-primary">Maintenance Progress</span>
                <span class="text-amber-600 dark:text-amber-400">In Progress</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-black/5 dark:bg-white/5">
                <div class="h-full w-2/3 animate-pulse rounded-full bg-gradient-to-r from-amber-500 to-yellow-500"></div>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <svg class="mt-0.5 size-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <p class="font-medium text-primary">What we're improving</p>
                <ul class="mt-2 space-y-1 text-muted">
                    <li>&bull; System performance optimizations</li>
                    <li>&bull; Security updates and patches</li>
                    <li>&bull; New features deployment</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <button onclick="location.reload()" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Check Status
    </button>
@endsection

@section('links')
    <a href="mailto:support@kingdomvitals.com" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Email Support</a>
@endsection
