@extends('errors::layout')

@section('code', '401')
@section('title', 'Authentication Required')
@section('icon-ping-color', 'rgba(168, 85, 247, 0.2)')
@section('icon-color-from', '#a855f7')
@section('icon-color-to', '#6366f1')
@section('label-color', 'text-purple-600 dark:text-purple-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
    </svg>
@endsection

@section('message')
    You need to sign in to access this page. Please log in with your credentials to continue.
@endsection

@section('description')
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 size-5 flex-shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
        <div>
            <p class="font-medium text-primary">Why am I seeing this?</p>
            <ul class="mt-2 space-y-1 text-muted">
                <li>&bull; Your session may have expired</li>
                <li>&bull; You might not be logged in</li>
                <li>&bull; The page requires authentication</li>
            </ul>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
        </svg>
        Sign In
    </a>
    <a href="{{ url('/') }}" class="rounded-full border border-black/10 bg-white/50 px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        Go Home
    </a>
@endsection

@section('links')
    <a href="{{ route('password.request') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Forgot Password?</a>
    <a href="{{ route('register') }}" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Create Account</a>
@endsection
