@php
    $impersonationData = session(\App\Services\TenantImpersonationService::SESSION_KEY);
@endphp

@if($impersonationData)
    <div class="fixed top-0 left-0 right-0 z-50 bg-amber-500 text-black px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon.shield-exclamation class="size-5" />
                <span class="font-medium">
                    Impersonating as Super Admin: {{ $impersonationData['super_admin_name'] }}
                </span>
                <span class="text-sm opacity-75">
                    (Session started {{ \Carbon\Carbon::parse($impersonationData['started_at'])->diffForHumans() }})
                </span>
            </div>
            <form action="{{ route('impersonate.exit') }}" method="POST">
                @csrf
                <button type="submit" class="bg-black text-white px-4 py-1 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                    Exit Impersonation
                </button>
            </form>
        </div>
    </div>
    <div class="h-10"></div> {{-- Spacer to push content down --}}
@endif
