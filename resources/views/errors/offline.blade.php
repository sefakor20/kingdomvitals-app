@extends('errors.layout')

@section('title', "You're offline")
@section('label', 'Connection Lost')
@section('code', '')
@section('icon-ping-color', 'rgba(239, 68, 68, 0.2)')
@section('icon-color-from', '#ef4444')
@section('icon-color-to', '#f97316')
@section('label-color', 'text-red-600 dark:text-red-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm6.28-7.466a8.25 8.25 0 0 0-12.56 0M21 10.5a12 12 0 0 0-18 0M3 3l18 18" />
    </svg>
@endsection

@section('message')
    Check your connection and we'll pick up where you left off.
@endsection

@section('actions')
    <button type="button" onclick="location.reload()" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Try again
    </button>
@endsection

@section('links')
    <a href="mailto:support@kingdomvitals.com" class="text-secondary transition hover:text-emerald-600 dark:hover:text-emerald-400">Email Support</a>
@endsection
