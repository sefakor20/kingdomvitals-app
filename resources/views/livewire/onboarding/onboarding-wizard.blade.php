<div class="space-y-8">
    {{-- Step indicators --}}
    <div class="flex items-center justify-center">
        <div class="flex items-center gap-0">
            @foreach([1 => 'Organization', 2 => 'Team', 3 => 'Integrations', 4 => 'Services', 5 => 'Complete'] as $step => $label)
                <div class="flex items-center">
                    {{-- Step circle --}}
                    <div class="relative">
                        <div @class([
                            'relative flex size-10 items-center justify-center rounded-full text-sm font-semibold transition-all duration-300',
                            'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25' => $this->currentStep > $step,
                            'bg-gradient-to-br from-emerald-500 to-lime-accent text-white shadow-lg shadow-emerald-500/30 ring-4 ring-emerald-500/20' => $this->currentStep === $step,
                            'bg-black/5 text-muted dark:bg-white/10' => $this->currentStep < $step,
                        ])>
                            @if($this->currentStep > $step)
                                <flux:icon name="check" variant="mini" class="size-5" />
                            @else
                                {{ $step }}
                            @endif

                            {{-- Pulse animation for current step --}}
                            @if($this->currentStep === $step)
                                <span class="absolute inset-0 animate-ping rounded-full bg-emerald-500/30"></span>
                            @endif
                        </div>

                        {{-- Step label (visible on larger screens) --}}
                        <span @class([
                            'absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-medium transition-colors hidden sm:block',
                            'text-emerald-600 dark:text-emerald-400' => $this->currentStep >= $step,
                            'text-muted' => $this->currentStep < $step,
                        ])>
                            {{ $label }}
                        </span>
                    </div>

                    {{-- Connector line --}}
                    @if($step < 5)
                        <div @class([
                            'mx-2 h-0.5 w-8 rounded-full transition-all duration-500 sm:w-12 lg:w-16',
                            'bg-gradient-to-r from-emerald-500 to-emerald-400' => $this->currentStep > $step,
                            'bg-black/10 dark:bg-white/10' => $this->currentStep <= $step,
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Extra spacing for step labels on larger screens --}}
    <div class="hidden sm:block sm:h-4"></div>

    {{-- Step content --}}
    <div class="rounded-2xl border border-black/10 bg-white/95 shadow-xl backdrop-blur-sm dark:border-white/10 dark:bg-obsidian-surface/95">
        <div class="p-6 sm:p-8">
            @switch($this->currentStep)
                @case(1)
                    @include('livewire.onboarding.steps.organization')
                    @break
                @case(2)
                    @include('livewire.onboarding.steps.team')
                    @break
                @case(3)
                    @include('livewire.onboarding.steps.integrations')
                    @break
                @case(4)
                    @include('livewire.onboarding.steps.services')
                    @break
                @case(5)
                    @include('livewire.onboarding.steps.complete')
                    @break
            @endswitch
        </div>
    </div>
</div>
