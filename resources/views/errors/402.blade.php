@extends('errors::layout')

@section('code', '402')
@section('title', 'Payment Required')
@section('icon-ping-color', 'rgba(16, 185, 129, 0.2)')
@section('icon-color-from', '#10b981')
@section('icon-color-to', '#84cc16')
@section('label-color', 'text-emerald-600 dark:text-emerald-400')
@section('title-gradient', 'text-gradient-emerald')

@section('icon')
    <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
    </svg>
@endsection

@section('message')
    This feature requires an active subscription. Upgrade your plan to unlock full access to all Kingdom Vitals features.
@endsection

@section('description')
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 size-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
        </svg>
        <div>
            <p class="font-medium text-primary">Premium features include</p>
            <ul class="mt-2 space-y-1 text-muted">
                <li>&bull; AI-powered insights and predictions</li>
                <li>&bull; Advanced reporting and analytics</li>
                <li>&bull; Unlimited team members</li>
                <li>&bull; Priority support</li>
            </ul>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/#pricing') }}" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
        </svg>
        View Plans
    </a>
    <button onclick="history.back()" class="rounded-full border border-black/10 bg-white/50 px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
        <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Go Back
    </button>
@endsection
