@if($this->currentBranch)
    <flux:navlist variant="outline">
        <flux:navlist.group :heading="__('Branch')" class="grid">
            @if($this->canViewMembers)
                <flux:navlist.item
                    icon="user-group"
                    :href="route('members.index', $this->currentBranch)"
                    :current="request()->routeIs('members.*')"
                    wire:navigate
                >
                    {{ __('Members') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewClusters)
                <flux:navlist.item
                    icon="rectangle-group"
                    :href="route('clusters.index', $this->currentBranch)"
                    :current="request()->routeIs('clusters.*')"
                    wire:navigate
                >
                    {{ __('Clusters') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewServices)
                <flux:navlist.item
                    icon="calendar"
                    :href="route('services.index', $this->currentBranch)"
                    :current="request()->routeIs('services.*')"
                    wire:navigate
                >
                    {{ __('Services') }}
                </flux:navlist.item>
            @endif
        </flux:navlist.group>
    </flux:navlist>
@endif
