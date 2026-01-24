<div class="max-w-full">
    <!-- Print Controls (hidden when printing) -->
    <div class="no-print mb-6 flex items-center justify-between">
        <flux:button href="{{ route('duty-rosters.index', $branch) }}" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Roster') }}
        </flux:button>
        <flux:button variant="primary" icon="printer" onclick="window.print()">
            {{ __('Print') }}
        </flux:button>
    </div>

    <!-- Print Header -->
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold uppercase">{{ tenant('name') ?? $branch->name }}</h1>
        <h2 class="mt-1 text-lg font-semibold uppercase">{{ $branch->name }}</h2>
        <h3 class="mt-2 text-base font-medium">SUNDAY DIVINE SERVICE PREACHING ROSTER</h3>
        <p class="mt-1 text-sm">{{ $this->dateRangeDisplay }}</p>
    </div>

    @if($this->dutyRosters->isEmpty())
        <div class="py-12 text-center">
            <p class="text-zinc-500">{{ __('No duty rosters found for this period.') }}</p>
        </div>
    @else
        <!-- Roster Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-zinc-400 text-sm">
                <thead>
                    <tr class="bg-zinc-100">
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('DATE') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('PREACHER') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('LITURGIST') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('THEME') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('SCRIPTURES') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('HYM.') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('READERS') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('GROUPS') }}</th>
                        <th class="border border-zinc-400 px-2 py-2 text-left font-semibold">{{ __('REMARKS') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->dutyRosters as $roster)
                        <tr wire:key="print-roster-{{ $roster->id }}">
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                <div class="font-medium">
                                    {{ $roster->service_date->format('jS') }}
                                </div>
                                <div class="text-xs uppercase">
                                    {{ $roster->service_date->format('M.') }}
                                </div>
                                <div class="text-xs">
                                    {{ $roster->service_date->format('Y') }}
                                </div>
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                {{ $roster->preacher_display_name ?? '-' }}
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                {{ $roster->liturgist_display_name ?? '-' }}
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @if($roster->theme)
                                    <span class="font-medium italic">{{ $roster->theme }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @if($roster->scriptures->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($roster->scriptures as $scripture)
                                            <div>{{ $scripture->reference }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @if($roster->hymn_numbers && count($roster->hymn_numbers) > 0)
                                    <div class="space-y-1">
                                        @foreach($roster->hymn_numbers as $hymn)
                                            <div>{{ $hymn }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @php
                                    $readers = $roster->scriptures
                                        ->filter(fn($s) => $s->reader_display_name)
                                        ->pluck('reader_display_name')
                                        ->unique()
                                        ->values();
                                @endphp
                                @if($readers->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($readers as $reader)
                                            <div>{{ $reader }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @if($roster->clusters->isNotEmpty())
                                    {{ $roster->clusters->pluck('name')->join(', ') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-zinc-400 px-2 py-2 align-top">
                                @if($roster->remarks)
                                    <span class="italic">{{ $roster->remarks }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
