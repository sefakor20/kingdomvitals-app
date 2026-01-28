<style>
    @page {
        size: 86mm 54mm; /* Credit card size */
        margin: 0;
    }
    @media print {
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
        .print-wrapper {
            min-height: auto !important;
            padding: 0 !important;
            background: white !important;
            display: block !important;
        }
        .id-card {
            page-break-inside: avoid;
            box-shadow: none !important;
            margin: 0 !important;
        }
    }
    .id-card {
        width: 86mm;
        height: 54mm;
    }
</style>

<div class="print-wrapper flex min-h-screen flex-col items-center justify-center bg-zinc-100 p-8 print:block print:min-h-0 print:bg-white print:p-0">
    <!-- Print Controls (hidden when printing) -->
    <div class="no-print mb-8 flex w-full max-w-md items-center justify-between">
        <flux:button href="{{ route('members.show', [$branch, $member]) }}" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Member') }}
        </flux:button>
        <flux:button variant="primary" icon="printer" onclick="window.print()">
            {{ __('Print Card') }}
        </flux:button>
    </div>

    <!-- ID Card -->
    <div class="id-card overflow-hidden rounded-xl bg-white shadow-xl print:rounded-none print:shadow-none">
        <!-- Header with gradient background -->
        <div class="relative h-16 bg-gradient-to-r from-blue-600 to-blue-800 px-4 pt-3">
            <h1 class="text-center text-sm font-bold uppercase tracking-wide text-white">
                {{ tenant('name') ?? $branch->name }}
            </h1>
            <p class="text-center text-xs text-blue-100">
                {{ __('Member ID Card') }}
            </p>
        </div>

        <!-- Card Body -->
        <div class="relative flex gap-3 px-3 pb-3">
            <!-- Photo (overlapping header) -->
            <div class="-mt-6 shrink-0">
                @if($member->photo_url)
                    <img
                        src="{{ $member->photo_url }}"
                        alt="{{ $member->fullName() }}"
                        class="size-16 rounded-lg border-4 border-white object-cover shadow-md print:shadow-none"
                    />
                @else
                    <div class="flex size-16 items-center justify-center rounded-lg border-4 border-white bg-zinc-100 shadow-md print:shadow-none">
                        <span class="text-xl font-bold text-zinc-400">
                            {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Member Info -->
            <div class="flex-1 pt-1">
                <h2 class="text-sm font-bold leading-tight text-zinc-900 line-clamp-2">
                    {{ $member->fullName() }}
                </h2>
                @if($member->membership_number)
                    <p class="text-[10px] font-medium text-zinc-600">{{ $member->membership_number }}</p>
                @endif
                <p class="text-[10px] text-zinc-500">{{ $branch->name }}</p>
            </div>

            <!-- QR Code -->
            <div class="-mt-4 shrink-0">
                <div class="rounded-lg border border-zinc-200 bg-white p-1 shadow-sm print:shadow-none">
                    <div class="size-14">
                        {!! $this->qrCodeSvg !!}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-zinc-100 bg-zinc-50 px-4 py-1.5">
            <p class="text-center text-[9px] text-zinc-400">
                {{ __('Scan QR code for check-in') }}
            </p>
        </div>
    </div>

    <!-- Card Preview Label (hidden when printing) -->
    <p class="no-print mt-6 text-center text-sm text-zinc-500">
        {{ __('Card preview (actual size: 86mm x 54mm)') }}
    </p>
</div>
