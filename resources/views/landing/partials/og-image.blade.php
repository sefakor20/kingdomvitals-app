{{-- OG Image Template for Landing Page --}}
<x-og-image>
    <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-950 p-12">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col items-center text-center">
            {{-- Logo --}}
            <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 ring-4 ring-white/20">
                <img src="{{ asset('favicon.svg') }}" alt="Kingdom Vitals" class="h-14 w-14">
            </div>

            {{-- Title --}}
            <h1 class="mb-3 text-5xl font-bold tracking-tight text-white">
                {{ config('app.name') }}
            </h1>

            {{-- Tagline --}}
            <p class="mb-6 max-w-2xl text-xl font-medium text-emerald-200">
                Church Management Made Simple
            </p>

            {{-- Features - 7 badges + AI accent --}}
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px;">
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Membership
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Giving
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Attendance
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Visitors
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Finances
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Events
                </span>
                <span style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: white;">
                    Communication
                </span>
                <span style="background: rgba(163,230,53,0.2); border-radius: 9999px; padding: 8px 20px; font-size: 16px; font-weight: 500; color: rgb(190,242,100);">
                    AI-Powered
                </span>
            </div>
        </div>
    </div>
</x-og-image>
