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

            @if($this->canViewVisitors)
                <flux:navlist.item
                    icon="user-plus"
                    :href="route('visitors.index', $this->currentBranch)"
                    :current="request()->routeIs('visitors.*')"
                    wire:navigate
                >
                    {{ __('Visitors') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewAttendance)
                <flux:navlist.item
                    icon="clipboard-document-check"
                    :href="route('attendance.index', $this->currentBranch)"
                    :current="request()->routeIs('attendance.*')"
                    wire:navigate
                >
                    {{ __('Attendance') }}
                </flux:navlist.item>
            @endif
        </flux:navlist.group>

        <flux:navlist.group :heading="__('Financial')" class="grid">
            @if($this->canViewDonations)
                <flux:navlist.item
                    icon="banknotes"
                    :href="route('donations.index', $this->currentBranch)"
                    :current="request()->routeIs('donations.*')"
                    wire:navigate
                >
                    {{ __('Donations') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewExpenses)
                <flux:navlist.item
                    icon="credit-card"
                    :href="route('expenses.index', $this->currentBranch)"
                    :current="request()->routeIs('expenses.*')"
                    wire:navigate
                >
                    {{ __('Expenses') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewPledges)
                <flux:navlist.item
                    icon="hand-raised"
                    :href="route('pledges.index', $this->currentBranch)"
                    :current="request()->routeIs('pledges.*')"
                    wire:navigate
                >
                    {{ __('Pledges') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewFinanceReports)
                <flux:navlist.item
                    icon="chart-bar"
                    :href="route('finance.reports', $this->currentBranch)"
                    :current="request()->routeIs('finance.reports')"
                    wire:navigate
                >
                    {{ __('Reports') }}
                </flux:navlist.item>
            @endif
        </flux:navlist.group>

        @if($this->canViewSms)
            <flux:navlist.group :heading="__('Communication')" class="grid">
                <flux:navlist.item
                    icon="chat-bubble-left-right"
                    :href="route('sms.index', $this->currentBranch)"
                    :current="request()->routeIs('sms.index', 'sms.compose')"
                    wire:navigate
                >
                    {{ __('SMS') }}
                </flux:navlist.item>
                <flux:navlist.item
                    icon="document-text"
                    :href="route('sms.templates', $this->currentBranch)"
                    :current="request()->routeIs('sms.templates')"
                    wire:navigate
                >
                    {{ __('Templates') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endif

        @if($this->canUpdateBranch)
            <flux:navlist.group :heading="__('Configuration')" class="grid">
                <flux:navlist.item
                    icon="cog-6-tooth"
                    :href="route('branches.settings', $this->currentBranch)"
                    :current="request()->routeIs('branches.settings')"
                    wire:navigate
                >
                    {{ __('Settings') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endif
    </flux:navlist>
@endif
