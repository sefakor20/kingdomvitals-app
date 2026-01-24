<div>
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

            @if($this->canViewDutyRosters)
                <flux:navlist.item
                    icon="calendar-days"
                    :href="route('duty-rosters.index', $this->currentBranch)"
                    :current="request()->routeIs('duty-rosters.*')"
                    wire:navigate
                >
                    {{ __('Duty Rosters') }}
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

            @if($this->canViewHouseholds)
                <flux:navlist.item
                    icon="home"
                    :href="route('households.index', $this->currentBranch)"
                    :current="request()->routeIs('households.*')"
                    wire:navigate
                >
                    {{ __('Households') }}
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

            @if($this->canViewEquipment)
                <flux:navlist.item
                    icon="wrench-screwdriver"
                    :href="route('equipment.index', $this->currentBranch)"
                    :current="request()->routeIs('equipment.*')"
                    wire:navigate
                >
                    {{ __('Equipment') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewPrayerRequests)
                <flux:navlist.item
                    icon="sparkles"
                    :href="route('prayer-requests.index', $this->currentBranch)"
                    :current="request()->routeIs('prayer-requests.*')"
                    wire:navigate
                >
                    {{ __('Prayer Requests') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewChildren)
                <flux:navlist.item
                    icon="academic-cap"
                    :href="route('children.index', $this->currentBranch)"
                    :current="request()->routeIs('children.*')"
                    wire:navigate
                >
                    {{ __('Children') }}
                </flux:navlist.item>
            @endif
        </flux:navlist.group>

        <flux:navlist.group :heading="__('Financial')" class="grid">
            @if($this->canViewFinanceReports)
                <flux:navlist.item
                    icon="presentation-chart-line"
                    :href="route('finance.dashboard', $this->currentBranch)"
                    :current="request()->routeIs('finance.dashboard')"
                    wire:navigate
                >
                    {{ __('Dashboard') }}
                </flux:navlist.item>
                <flux:navlist.item
                    icon="heart"
                    :href="route('finance.donor-engagement', $this->currentBranch)"
                    :current="request()->routeIs('finance.donor-engagement')"
                    wire:navigate
                >
                    {{ __('Donor Engagement') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewGivingHistory)
                <flux:navlist.item
                    icon="gift"
                    :href="route('giving.history', $this->currentBranch)"
                    :current="request()->routeIs('giving.history')"
                    wire:navigate
                >
                    {{ __('My Giving') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewDonations)
                <flux:navlist.item
                    icon="banknotes"
                    :href="route('donations.index', $this->currentBranch)"
                    :current="request()->routeIs('donations.*')"
                    wire:navigate
                >
                    {{ __('Donations') }}
                </flux:navlist.item>
                <flux:navlist.item
                    icon="currency-dollar"
                    :href="route('offerings.index', $this->currentBranch)"
                    :current="request()->routeIs('offerings.*')"
                    wire:navigate
                >
                    {{ __('Offerings') }}
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
                <flux:navlist.item
                    icon="flag"
                    :href="route('campaigns.index', $this->currentBranch)"
                    :current="request()->routeIs('campaigns.*')"
                    wire:navigate
                >
                    {{ __('Campaigns') }}
                </flux:navlist.item>
            @endif

            @if($this->canViewBudgets)
                <flux:navlist.item
                    icon="calculator"
                    :href="route('budgets.index', $this->currentBranch)"
                    :current="request()->routeIs('budgets.*')"
                    wire:navigate
                >
                    {{ __('Budgets') }}
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

        @if($this->canViewReports)
            <flux:navlist.group :heading="__('Analytics')" class="grid">
                <flux:navlist.item
                    icon="chart-bar-square"
                    :href="route('reports.index', $this->currentBranch)"
                    :current="request()->routeIs('reports.*')"
                    wire:navigate
                >
                    {{ __('Report Center') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endif

        @if($this->canUpdateBranch || $this->canViewUsers)
            <flux:navlist.group :heading="__('Configuration')" class="grid">
                @if($this->canViewUsers)
                    <flux:navlist.item
                        icon="users"
                        :href="route('branches.users.index', $this->currentBranch)"
                        :current="request()->routeIs('branches.users.*')"
                        wire:navigate
                    >
                        {{ __('Users') }}
                    </flux:navlist.item>
                @endif
                @if($this->canUpdateBranch)
                    <flux:navlist.item
                        icon="cog-6-tooth"
                        :href="route('branches.settings', $this->currentBranch)"
                        :current="request()->routeIs('branches.settings')"
                        wire:navigate
                    >
                        {{ __('Settings') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>
        @endif
    </flux:navlist>
@endif
</div>
