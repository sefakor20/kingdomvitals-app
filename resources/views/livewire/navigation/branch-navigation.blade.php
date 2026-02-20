<div>
@if($this->currentBranch)
    <flux:sidebar.nav>
        {{-- People Group --}}
        @if($this->canViewMembers || $this->canViewHouseholds || $this->canViewChildren)
            <flux:sidebar.group icon="users" :heading="__('Membership')" expandable :expanded="request()->routeIs('members.*', 'households.*', 'children.*')" class="grid">
                @if($this->canViewMembers)
                    <flux:sidebar.item
                        icon="user-group"
                        :href="route('members.index', $this->currentBranch)"
                        :current="request()->routeIs('members.*')"
                        wire:navigate
                    >
                        {{ __('Members') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewHouseholds)
                    <flux:sidebar.item
                        icon="home"
                        :href="route('households.index', $this->currentBranch)"
                        :current="request()->routeIs('households.*')"
                        wire:navigate
                    >
                        {{ __('Households') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewChildren)
                    <flux:sidebar.item
                        icon="academic-cap"
                        :href="route('children.index', $this->currentBranch)"
                        :current="request()->routeIs('children.*')"
                        wire:navigate
                    >
                        {{ __('Children') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Visitors Group --}}
        @if($this->canViewVisitors || $this->canViewFollowUpQueue || $this->canViewFollowUpTemplates)
            <flux:sidebar.group icon="user-plus" :heading="__('Visitors')" expandable :expanded="request()->routeIs('visitors.*')" class="grid">
                @if($this->canViewVisitors)
                    <flux:sidebar.item
                        icon="user-plus"
                        :href="route('visitors.index', $this->currentBranch)"
                        :current="request()->routeIs('visitors.index', 'visitors.show')"
                        wire:navigate
                    >
                        {{ __('All Visitors') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewFollowUpQueue)
                    <flux:sidebar.item
                        icon="clock"
                        :href="route('visitors.follow-ups', $this->currentBranch)"
                        :current="request()->routeIs('visitors.follow-ups')"
                        wire:navigate
                    >
                        {{ __('Follow-Up Queue') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewFollowUpTemplates)
                    <flux:sidebar.item
                        icon="document-text"
                        :href="route('visitors.follow-up-templates', $this->currentBranch)"
                        :current="request()->routeIs('visitors.follow-up-templates')"
                        wire:navigate
                    >
                        {{ __('Follow-Up Templates') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewVisitors)
                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route('visitors.analytics', $this->currentBranch)"
                        :current="request()->routeIs('visitors.analytics')"
                        wire:navigate
                    >
                        {{ __('Analytics') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Operations Group --}}
        @if($this->canViewServices || $this->canViewDutyRosters || $this->canViewAttendance || $this->canViewClusters)
            <flux:sidebar.group icon="clipboard-document-list" :heading="__('Operations')" expandable :expanded="request()->routeIs('services.*', 'duty-rosters.*', 'attendance.*', 'clusters.*')" class="grid">
                @if($this->canViewServices)
                    <flux:sidebar.item
                        icon="calendar"
                        :href="route('services.index', $this->currentBranch)"
                        :current="request()->routeIs('services.*')"
                        wire:navigate
                    >
                        {{ __('Services') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewAttendance)
                    <flux:sidebar.item
                        icon="clipboard-document-check"
                        :href="route('attendance.index', $this->currentBranch)"
                        :current="request()->routeIs('attendance.*')"
                        wire:navigate
                    >
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewDutyRosters)
                    <flux:sidebar.item
                        icon="calendar-days"
                        :href="route('duty-rosters.index', $this->currentBranch)"
                        :current="request()->routeIs('duty-rosters.*')"
                        wire:navigate
                    >
                        {{ __('Duty Rosters') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewClusters)
                    <flux:sidebar.item
                        icon="rectangle-group"
                        :href="route('clusters.index', $this->currentBranch)"
                        :current="request()->routeIs('clusters.*')"
                        wire:navigate
                    >
                        {{ __('Clusters') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Assets & Care Group --}}
        @if($this->canViewEquipment || $this->canViewPrayerRequests)
            <flux:sidebar.group icon="heart" :heading="__('Assets & Care')" expandable :expanded="request()->routeIs('equipment.*', 'prayer-requests.*')" class="grid">
                @if($this->canViewEquipment)
                    <flux:sidebar.item
                        icon="wrench-screwdriver"
                        :href="route('equipment.index', $this->currentBranch)"
                        :current="request()->routeIs('equipment.*')"
                        wire:navigate
                    >
                        {{ __('Equipment') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewPrayerRequests)
                    <flux:sidebar.item
                        icon="sparkles"
                        :href="route('prayer-requests.index', $this->currentBranch)"
                        :current="request()->routeIs('prayer-requests.*')"
                        wire:navigate
                    >
                        {{ __('Prayer Requests') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Financial Group with nested sub-groups --}}
        @if($this->canViewFinanceReports || $this->canViewGivingHistory || $this->canViewDonations || $this->canViewExpenses || $this->canViewPledges || $this->canViewBudgets)
            <flux:sidebar.group icon="banknotes" :heading="__('Financial')" expandable :expanded="request()->routeIs('finance.*', 'giving.*', 'donations.*', 'offerings.*', 'expenses.*', 'pledges.*', 'campaigns.*', 'budgets.*')" class="grid">
                {{-- Overview --}}
                @if($this->canViewFinanceReports)
                    <flux:sidebar.item
                        icon="presentation-chart-line"
                        :href="route('finance.dashboard', $this->currentBranch)"
                        :current="request()->routeIs('finance.dashboard')"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="heart"
                        :href="route('finance.donor-engagement', $this->currentBranch)"
                        :current="request()->routeIs('finance.donor-engagement')"
                        wire:navigate
                    >
                        {{ __('Donor Engagement') }}
                    </flux:sidebar.item>
                @endif

                {{-- Income --}}
                @if($this->canViewGivingHistory)
                    <flux:sidebar.item
                        icon="gift"
                        :href="route('giving.history', $this->currentBranch)"
                        :current="request()->routeIs('giving.history')"
                        wire:navigate
                    >
                        {{ __('My Giving') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewDonations)
                    <flux:sidebar.item
                        icon="banknotes"
                        :href="route('donations.index', $this->currentBranch)"
                        :current="request()->routeIs('donations.*')"
                        wire:navigate
                    >
                        {{ __('Donations') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="currency-dollar"
                        :href="route('offerings.index', $this->currentBranch)"
                        :current="request()->routeIs('offerings.*')"
                        wire:navigate
                    >
                        {{ __('Offerings') }}
                    </flux:sidebar.item>
                @endif

                {{-- Tracking --}}
                @if($this->canViewExpenses)
                    <flux:sidebar.item
                        icon="credit-card"
                        :href="route('expenses.index', $this->currentBranch)"
                        :current="request()->routeIs('expenses.*')"
                        wire:navigate
                    >
                        {{ __('Expenses') }}
                    </flux:sidebar.item>
                @endif

                {{-- Planning --}}
                @if($this->canViewPledges)
                    <flux:sidebar.item
                        icon="hand-raised"
                        :href="route('pledges.index', $this->currentBranch)"
                        :current="request()->routeIs('pledges.*')"
                        wire:navigate
                    >
                        {{ __('Pledges') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="flag"
                        :href="route('campaigns.index', $this->currentBranch)"
                        :current="request()->routeIs('campaigns.*')"
                        wire:navigate
                    >
                        {{ __('Campaigns') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewBudgets)
                    <flux:sidebar.item
                        icon="calculator"
                        :href="route('budgets.index', $this->currentBranch)"
                        :current="request()->routeIs('budgets.*')"
                        wire:navigate
                    >
                        {{ __('Budgets') }}
                    </flux:sidebar.item>
                @endif

                @if($this->canViewFinanceReports)
                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route('finance.reports', $this->currentBranch)"
                        :current="request()->routeIs('finance.reports')"
                        wire:navigate
                    >
                        {{ __('Reports') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Communication Group --}}
        @if($this->canViewSms)
            <flux:sidebar.group icon="chat-bubble-left-right" :heading="__('Communication')" expandable :expanded="request()->routeIs('sms.*')" class="grid">
                <flux:sidebar.item
                    icon="chat-bubble-left-right"
                    :href="route('sms.index', $this->currentBranch)"
                    :current="request()->routeIs('sms.index', 'sms.compose')"
                    wire:navigate
                >
                    {{ __('SMS') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="document-text"
                    :href="route('sms.templates', $this->currentBranch)"
                    :current="request()->routeIs('sms.templates')"
                    wire:navigate
                >
                    {{ __('Templates') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        @endif

        {{-- Analytics Group --}}
        @if($this->canViewReports || $this->canViewAiInsights)
            <flux:sidebar.group icon="chart-bar-square" :heading="__('Analytics')" expandable :expanded="request()->routeIs('reports.*', 'ai-insights.*')" class="grid">
                @if($this->canViewAiInsights)
                    <flux:sidebar.item
                        icon="sparkles"
                        :href="route('ai-insights.dashboard', $this->currentBranch)"
                        :current="request()->routeIs('ai-insights.*')"
                        wire:navigate
                    >
                        {{ __('AI Insights') }}
                    </flux:sidebar.item>
                @endif
                @if($this->canViewReports)
                    <flux:sidebar.item
                        icon="chart-bar-square"
                        :href="route('reports.index', $this->currentBranch)"
                        :current="request()->routeIs('reports.*')"
                        wire:navigate
                    >
                        {{ __('Report Center') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif

        {{-- Configuration Group --}}
        @if($this->canUpdateBranch || $this->canViewUsers)
            <flux:sidebar.group icon="cog-6-tooth" :heading="__('Configuration')" expandable :expanded="request()->routeIs('branches.users.*', 'branches.settings')" class="grid">
                @if($this->canViewUsers)
                    <flux:sidebar.item
                        icon="users"
                        :href="route('branches.users.index', $this->currentBranch)"
                        :current="request()->routeIs('branches.users.*')"
                        wire:navigate
                    >
                        {{ __('Users') }}
                    </flux:sidebar.item>
                @endif
                @if($this->canUpdateBranch)
                    <flux:sidebar.item
                        icon="cog-6-tooth"
                        :href="route('branches.settings', $this->currentBranch)"
                        :current="request()->routeIs('branches.settings')"
                        wire:navigate
                    >
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        @endif
    </flux:sidebar.nav>
@endif
</div>
