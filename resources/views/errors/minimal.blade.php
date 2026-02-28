@extends('errors::layout')

@section('icon-ping-color', 'rgba(113, 113, 122, 0.2)')
@section('icon-color-from', '#71717a')
@section('icon-color-to', '#52525b')
@section('label-color', 'text-zinc-600 dark:text-zinc-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
    </svg>
@endsection

@section('message')
    Something unexpected happened. Please try again or contact support if the problem persists.
@endsection

@section('actions')
    <button onclick="history.back()" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Go Back
    </button>
    <a href="{{ url('/') }}" class="rounded-full border border-black/10 bg-white/50 px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        Go Home
    </a>
@endsection
