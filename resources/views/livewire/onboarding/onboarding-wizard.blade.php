<div class="space-y-8">
    <!-- Step indicators -->
    <div class="flex items-center justify-center space-x-2">
        @foreach([1 => 'Organization', 2 => 'Team', 3 => 'Integrations', 4 => 'Services', 5 => 'Complete'] as $step => $label)
            <div class="flex items-center">
                <div @class([
                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                    'bg-emerald-600 text-white' => $this->currentStep >= $step,
                    'bg-stone-200 text-stone-600 dark:bg-stone-700 dark:text-stone-400' => $this->currentStep < $step,
                ])>
                    @if($this->currentStep > $step)
                        <flux:icon name="check" variant="micro" />
                    @else
                        {{ $step }}
                    @endif
                </div>
                @if($step < 5)
                    <div @class([
                        'h-0.5 w-8 mx-2',
                        'bg-emerald-600' => $this->currentStep > $step,
                        'bg-stone-200 dark:bg-stone-700' => $this->currentStep <= $step,
                    ])></div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Step content -->
    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 shadow-sm">
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
