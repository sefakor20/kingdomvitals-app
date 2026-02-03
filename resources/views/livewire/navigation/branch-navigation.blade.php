<div>
@if($this->currentBranch)
    <flux:navlist variant="outline">
        {{-- People Group --}}
        @if($this->canViewMembers || $this->canViewHouseholds || $this->canViewChildren)
            <flux:navlist.group :heading="__('Membership')" expandable :expanded="request()->routeIs('members.*', 'households.*', 'children.*')" class="grid">
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
        @endif

        {{-- Visitors Group --}}
        @if($this->canViewVisitors || $this->canViewFollowUpQueue || $this->canViewFollowUpTemplates)
            <flux:navlist.group :heading="__('Visitors')" expandable :expanded="request()->routeIs('visitors.*')" class="grid">
                @if($this->canViewVisitors)
                    <flux:navlist.item
                        icon="user-plus"
                        :href="route('visitors.index', $this->currentBranch)"
                        :current="request()->routeIs('visitors.index', 'visitors.show')"
                        wire:navigate
                    >
                        {{ __('All Visitors') }}
                    </flux:navlist.item>
                @endif

                @if($this->canViewFollowUpQueue)
                    <flux:navlist.item
                        icon="clock"
                        :href="route('visitors.follow-ups', $this->currentBranch)"
                        :current="request()->routeIs('visitors.follow-ups')"
                        wire:navigate
                    >
                        {{ __('Follow-Up Queue') }}
                    </flux:navlist.item>
                @endif

                @if($this->canViewFollowUpTemplates)
                    <flux:navlist.item
                        icon="document-text"
                        :href="route('visitors.follow-up-templates', $this->currentBranch)"
                        :current="request()->routeIs('visitors.follow-up-templates')"
                        wire:navigate
                    >
                        {{ __('Follow-Up Templates') }}
                    </flux:navlist.item>
                @endif

                @if($this->canViewVisitors)
                    <flux:navlist.item
                        icon="chart-bar"
                        :href="route('visitors.analytics', $this->currentBranch)"
                        :current="request()->routeIs('visitors.analytics')"
                        wire:navigate
                    >
                        {{ __('Analytics') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>
        @endif

        {{-- Operations Group --}}
        @if($this->canViewServices || $this->canViewDutyRosters || $this->canViewAttendance || $this->canViewClusters)
            <flux:navlist.group :heading="__('Operations')" expandable :expanded="request()->routeIs('services.*', 'duty-rosters.*', 'attendance.*', 'clusters.*')" class="grid">
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
            </flux:navlist.group>
        @endif

        {{-- Assets & Care Group --}}
        @if($this->canViewEquipment || $this->canViewPrayerRequests)
            <flux:navlist.group :heading="__('Assets & Care')" expandable :expanded="request()->routeIs('equipment.*', 'prayer-requests.*')" class="grid">
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
            </flux:navlist.group>
        @endif

        {{-- Financial Group with nested sub-groups --}}
        @if($this->canViewFinanceReports || $this->canViewGivingHistory || $this->canViewDonations || $this->canViewExpenses || $this->canViewPledges || $this->canViewBudgets)
            <flux:navlist.group :heading="__('Financial')" expandable :expanded="request()->routeIs('finance.*', 'giving.*', 'donations.*', 'offerings.*', 'expenses.*', 'pledges.*', 'campaigns.*', 'budgets.*')" class="grid">
                {{-- Overview --}}
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

                {{-- Income --}}
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

                {{-- Tracking --}}
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

                {{-- Planning --}}
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
        @endif

        {{-- Communication Group --}}
        @if($this->canViewSms)
            <flux:navlist.group :heading="__('Communication')" expandable :expanded="request()->routeIs('sms.*')" class="grid">
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

        {{-- Analytics Group --}}
        @if($this->canViewReports)
            <flux:navlist.group :heading="__('Analytics')" expandable :expanded="request()->routeIs('reports.*')" class="grid">
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

        {{-- Configuration Group --}}
        @if($this->canUpdateBranch || $this->canViewUsers)
            <flux:navlist.group :heading="__('Configuration')" expandable :expanded="request()->routeIs('branches.users.*', 'branches.settings')" class="grid">
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
