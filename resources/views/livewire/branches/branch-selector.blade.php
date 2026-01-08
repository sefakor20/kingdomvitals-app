<div>
    @if($this->branches->isNotEmpty())
        <flux:dropdown position="bottom" align="start">
            <flux:button variant="ghost" class="w-full justify-start overflow-hidden" icon="building-office" icon:trailing="chevron-down">
                <span class="truncate max-w-[140px] block">
                    {{ $this->currentBranch?->name ?? __('Select Branch') }}
                </span>
            </flux:button>

            <flux:menu class="w-[200px]">
                @foreach($this->branches as $branch)
                    <flux:menu.item
                        wire:click="switchBranch('{{ $branch->id }}')"
                        wire:key="branch-select-{{ $branch->id }}"
                        class="{{ $currentBranchId === $branch->id ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="truncate">{{ $branch->name }}</span>
                            @if($branch->is_main)
                                <flux:badge size="sm" color="blue">{{ __('Main') }}</flux:badge>
                            @endif
                        </div>
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
