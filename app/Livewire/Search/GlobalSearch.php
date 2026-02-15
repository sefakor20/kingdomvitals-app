<?php

declare(strict_types=1);

namespace App\Livewire\Search;

use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\SearchAnalytic;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\BranchContextService;
use App\Services\PlanAccessService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public ?string $currentBranchId = null;

    public int $selectedIndex = -1;

    public bool $searchAllBranches = false;

    /** @var array<int, string> */
    public array $recentSearches = [];

    private const MAX_RESULTS_PER_TYPE = 5;

    private const MAX_RECENT_SEARCHES = 5;

    public function mount(BranchContextService $branchContext): void
    {
        $this->currentBranchId = $branchContext->getCurrentBranchId()
            ?? $branchContext->getDefaultBranchId();
        $this->recentSearches = session()->get('global_search.recent', []);
    }

    #[On('branch-switched')]
    public function handleBranchSwitch(string $branchId): void
    {
        $this->currentBranchId = $branchId;
        $this->resetSearch();
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetSearch();
    }

    public function resetSearch(): void
    {
        $this->search = '';
        $this->selectedIndex = -1;
        $this->searchAllBranches = false;
        unset($this->results);
        unset($this->accessibleBranchIds);
    }

    /**
     * Reset selection when search term changes.
     */
    public function updatedSearch(): void
    {
        $this->selectedIndex = -1;
    }

    /**
     * Toggle between searching current branch and all accessible branches.
     */
    public function toggleSearchScope(): void
    {
        $this->searchAllBranches = ! $this->searchAllBranches;
        $this->selectedIndex = -1;
        unset($this->results);
    }

    /**
     * Get branch IDs the current user has access to.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function accessibleBranchIds(): array
    {
        return auth()->user()
            ->branchAccess()
            ->pluck('branch_id')
            ->toArray();
    }

    /**
     * Move selection to the next result.
     */
    public function selectNext(): void
    {
        $flatResults = $this->getFlatResults();
        $maxIndex = count($flatResults) - 1;

        if ($maxIndex < 0) {
            return;
        }

        $this->selectedIndex = min($this->selectedIndex + 1, $maxIndex);
    }

    /**
     * Move selection to the previous result.
     */
    public function selectPrevious(): void
    {
        $this->selectedIndex = max($this->selectedIndex - 1, 0);
    }

    /**
     * Select the currently highlighted result.
     */
    public function selectCurrent(): void
    {
        $flatResults = $this->getFlatResults();

        if ($this->selectedIndex >= 0 && isset($flatResults[$this->selectedIndex])) {
            $item = $flatResults[$this->selectedIndex];
            $this->selectResult($item['type'], $item['id'], $item['branch_id'] ?? null);
        }
    }

    /**
     * Get a flat array of all results for keyboard navigation.
     *
     * @return array<int, array{type: string, id: string, branch_id: ?string}>
     */
    private function getFlatResults(): array
    {
        $flat = [];
        foreach ($this->results as $type => $data) {
            $items = $data['items'] ?? $data;
            foreach ($items as $item) {
                $flat[] = [
                    'type' => $type,
                    'id' => $item['id'],
                    'branch_id' => $item['branch_id'] ?? null,
                ];
            }
        }

        return $flat;
    }

    /**
     * Save a search term to recent searches.
     */
    private function saveRecentSearch(string $term): void
    {
        if (strlen($term) < 2) {
            return;
        }

        // Remove if already exists, then prepend
        $this->recentSearches = array_values(array_filter(
            $this->recentSearches,
            fn (string $s): bool => $s !== $term
        ));
        array_unshift($this->recentSearches, $term);

        // Keep only max recent searches
        $this->recentSearches = array_slice($this->recentSearches, 0, self::MAX_RECENT_SEARCHES);

        session()->put('global_search.recent', $this->recentSearches);
    }

    /**
     * Use a recent search term.
     */
    public function useRecentSearch(string $term): void
    {
        $this->search = $term;
        $this->selectedIndex = -1;
        unset($this->results);
    }

    /**
     * Clear all recent searches.
     */
    public function clearRecentSearches(): void
    {
        $this->recentSearches = [];
        session()->forget('global_search.recent');
    }

    /**
     * Highlight matching text in a string.
     */
    public function highlightMatch(?string $text, string $search): string
    {
        if ($text === null || $text === '' || strlen($search) < 2) {
            return e($text ?? '');
        }

        $escapedText = e($text);
        $escapedSearch = preg_quote($search, '/');

        return preg_replace(
            '/('.$escapedSearch.')/i',
            '<mark class="bg-yellow-200 dark:bg-yellow-700/50 rounded px-0.5">$1</mark>',
            $escapedText
        ) ?? $escapedText;
    }

    /**
     * Navigate to a selected search result.
     */
    public function selectResult(string $type, string $id, ?string $branchId = null): void
    {
        // Use provided branch_id (for all-branches search) or current branch
        $branch = $branchId ?? $this->currentBranchId;

        $route = match ($type) {
            'members' => route('members.show', ['branch' => $branch, 'member' => $id]),
            'visitors' => route('visitors.show', ['branch' => $branch, 'visitor' => $id]),
            'services' => route('services.show', ['branch' => $branch, 'service' => $id]),
            'households' => route('households.show', ['branch' => $branch, 'household' => $id]),
            'equipment' => route('equipment.show', ['branch' => $branch, 'equipment' => $id]),
            'clusters' => route('clusters.show', ['branch' => $branch, 'cluster' => $id]),
            'prayer_requests' => route('prayer-requests.show', ['branch' => $branch, 'prayerRequest' => $id]),
            default => route('dashboard'),
        };

        // Log search analytics
        $this->logSearchAnalytics($type, $id);

        $this->saveRecentSearch($this->search);
        $this->closeModal();
        $this->redirect($route, navigate: true);
    }

    /**
     * Log search analytics when a result is selected.
     */
    private function logSearchAnalytics(string $selectedType, string $selectedId): void
    {
        if (strlen($this->search) < 2) {
            return;
        }

        $resultsByType = [];
        $totalCount = 0;

        foreach ($this->results as $type => $data) {
            $count = $data['total'] ?? count($data['items'] ?? $data);
            $resultsByType[$type] = $count;
            $totalCount += $count;
        }

        SearchAnalytic::log($this->search, [
            'branch_id' => $this->currentBranchId,
            'searched_all_branches' => $this->searchAllBranches,
            'results_count' => $totalCount,
            'results_by_type' => $resultsByType,
            'selected_type' => $selectedType,
            'selected_id' => $selectedId,
        ]);
    }

    /**
     * Navigate to view all results of a specific type.
     */
    public function viewAllResults(string $type): void
    {
        $branch = $this->currentBranchId;
        $searchParam = ['search' => $this->search];

        $route = match ($type) {
            'members' => route('members.index', array_merge(['branch' => $branch], $searchParam)),
            'visitors' => route('visitors.index', array_merge(['branch' => $branch], $searchParam)),
            'services' => route('services.index', array_merge(['branch' => $branch], $searchParam)),
            'households' => route('households.index', array_merge(['branch' => $branch], $searchParam)),
            'equipment' => route('equipment.index', array_merge(['branch' => $branch], $searchParam)),
            'clusters' => route('clusters.index', array_merge(['branch' => $branch], $searchParam)),
            'prayer_requests' => route('prayer-requests.index', array_merge(['branch' => $branch], $searchParam)),
            default => route('dashboard'),
        };

        $this->saveRecentSearch($this->search);
        $this->closeModal();
        $this->redirect($route, navigate: true);
    }

    /**
     * Execute a quick action (navigate to create page).
     */
    public function executeQuickAction(string $routeName): void
    {
        $route = route($routeName, ['branch' => $this->currentBranchId]);
        $this->closeModal();
        $this->redirect($route, navigate: true);
    }

    /**
     * Get the current branch.
     */
    #[Computed]
    public function currentBranch(): ?Branch
    {
        if (! $this->currentBranchId) {
            return null;
        }

        return Branch::find($this->currentBranchId);
    }

    /**
     * Get available quick actions based on plan modules.
     *
     * @return array<int, array{label: string, icon: string, route: string}>
     */
    #[Computed]
    public function quickActions(): array
    {
        $planAccess = app(PlanAccessService::class);
        $actions = [];

        if ($planAccess->hasModule(PlanModule::Members)) {
            $actions[] = ['label' => __('Members'), 'icon' => 'users', 'route' => 'members.index'];
        }

        if ($planAccess->hasModule(PlanModule::Visitors)) {
            $actions[] = ['label' => __('Visitors'), 'icon' => 'user-plus', 'route' => 'visitors.index'];
        }

        if ($planAccess->hasModule(PlanModule::Households)) {
            $actions[] = ['label' => __('Households'), 'icon' => 'home', 'route' => 'households.index'];
        }

        if ($planAccess->hasModule(PlanModule::Services)) {
            $actions[] = ['label' => __('Services'), 'icon' => 'calendar', 'route' => 'services.index'];
        }

        if ($planAccess->hasModule(PlanModule::Clusters)) {
            $actions[] = ['label' => __('Clusters'), 'icon' => 'users', 'route' => 'clusters.index'];
        }

        if ($planAccess->hasModule(PlanModule::PrayerRequests)) {
            $actions[] = ['label' => __('Prayer Requests'), 'icon' => 'heart', 'route' => 'prayer-requests.index'];
        }

        return $actions;
    }

    /**
     * Apply branch filter to a query based on search scope.
     */
    private function applyBranchFilter(Builder $query, string $branchColumn): Builder
    {
        if ($this->searchAllBranches) {
            return $query->whereIn($branchColumn, $this->accessibleBranchIds);
        }

        return $query->where($branchColumn, $this->currentBranchId);
    }

    /**
     * Build fuzzy search conditions using SOUNDEX for name matching.
     */
    private function buildFuzzyConditions(Builder $query, array $columns, string $term): Builder
    {
        $searchTerm = '%'.$term.'%';

        return $query->where(function ($q) use ($columns, $searchTerm, $term): void {
            foreach ($columns as $column) {
                // Standard LIKE match
                $q->orWhere($column, 'like', $searchTerm);

                // SOUNDEX match for name-like columns (phonetic matching)
                if (Str::contains($column, ['first_name', 'last_name', 'name'])) {
                    $q->orWhereRaw("SOUNDEX($column) = SOUNDEX(?)", [$term]);
                }
            }
        });
    }

    /**
     * Get search results grouped by type with counts.
     *
     * @return array<string, array{items: Collection, total: int}>
     */
    #[Computed]
    public function results(): array
    {
        if (strlen($this->search) < 2) {
            return [];
        }

        // When not searching all branches, require current branch
        if (! $this->searchAllBranches && ! $this->currentBranchId) {
            return [];
        }

        $planAccess = app(PlanAccessService::class);
        $results = [];
        $searchTerm = '%'.$this->search.'%';

        // Members
        if ($planAccess->hasModule(PlanModule::Members)) {
            $baseQuery = Member::query();
            $this->applyBranchFilter($baseQuery, 'primary_branch_id');
            $this->buildFuzzyConditions($baseQuery, ['first_name', 'last_name', 'email', 'phone', 'membership_number'], $this->search);

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $members = $baseQuery
                    ->with($this->searchAllBranches ? ['primaryBranch:id,name'] : [])
                    ->select(['id', 'first_name', 'last_name', 'membership_number', 'email', 'primary_branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Member $m): array => [
                        'id' => $m->id,
                        'title' => $m->fullName(),
                        'subtitle' => $m->membership_number ?? $m->email,
                        'icon' => 'user',
                        'branch_id' => $m->primary_branch_id,
                        'branch_name' => $this->searchAllBranches ? $m->primaryBranch?->name : null,
                    ]);

                $results['members'] = ['items' => $members, 'total' => $total];
            }
        }

        // Visitors
        if ($planAccess->hasModule(PlanModule::Visitors)) {
            $baseQuery = Visitor::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $this->buildFuzzyConditions($baseQuery, ['first_name', 'last_name', 'email', 'phone'], $this->search);

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $visitors = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'first_name', 'last_name', 'email', 'visit_date', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Visitor $v): array => [
                        'id' => $v->id,
                        'title' => $v->fullName(),
                        'subtitle' => $v->visit_date?->format('M j, Y'),
                        'icon' => 'user-plus',
                        'branch_id' => $v->branch_id,
                        'branch_name' => $this->searchAllBranches ? $v->branch?->name : null,
                    ]);

                $results['visitors'] = ['items' => $visitors, 'total' => $total];
            }
        }

        // Services
        if ($planAccess->hasModule(PlanModule::Services)) {
            $baseQuery = Service::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $baseQuery->where('name', 'like', $searchTerm);

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $services = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'name', 'day_of_week', 'time', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Service $s): array => [
                        'id' => $s->id,
                        'title' => $s->name,
                        'subtitle' => $s->day_of_week !== null ? now()->startOfWeek()->addDays($s->day_of_week)->format('l') : null,
                        'icon' => 'calendar',
                        'branch_id' => $s->branch_id,
                        'branch_name' => $this->searchAllBranches ? $s->branch?->name : null,
                    ]);

                $results['services'] = ['items' => $services, 'total' => $total];
            }
        }

        // Households
        if ($planAccess->hasModule(PlanModule::Households)) {
            $baseQuery = Household::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $this->buildFuzzyConditions($baseQuery, ['name'], $this->search);

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $households = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'name', 'address', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Household $h): array => [
                        'id' => $h->id,
                        'title' => $h->name,
                        'subtitle' => $h->address,
                        'icon' => 'home',
                        'branch_id' => $h->branch_id,
                        'branch_name' => $this->searchAllBranches ? $h->branch?->name : null,
                    ]);

                $results['households'] = ['items' => $households, 'total' => $total];
            }
        }

        // Clusters
        if ($planAccess->hasModule(PlanModule::Clusters)) {
            $baseQuery = Cluster::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $this->buildFuzzyConditions($baseQuery, ['name'], $this->search);

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $clusters = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'name', 'description', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Cluster $c): array => [
                        'id' => $c->id,
                        'title' => $c->name,
                        'subtitle' => $c->description ? Str::limit($c->description, 50) : null,
                        'icon' => 'users',
                        'branch_id' => $c->branch_id,
                        'branch_name' => $this->searchAllBranches ? $c->branch?->name : null,
                    ]);

                $results['clusters'] = ['items' => $clusters, 'total' => $total];
            }
        }

        // Equipment
        if ($planAccess->hasModule(PlanModule::Equipment)) {
            $baseQuery = Equipment::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $baseQuery->where(function ($query) use ($searchTerm): void {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('serial_number', 'like', $searchTerm);
            });

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $equipment = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'name', 'serial_number', 'condition', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (Equipment $e): array => [
                        'id' => $e->id,
                        'title' => $e->name,
                        'subtitle' => $e->serial_number,
                        'icon' => 'wrench',
                        'branch_id' => $e->branch_id,
                        'branch_name' => $this->searchAllBranches ? $e->branch?->name : null,
                    ]);

                $results['equipment'] = ['items' => $equipment, 'total' => $total];
            }
        }

        // Prayer Requests
        if ($planAccess->hasModule(PlanModule::PrayerRequests)) {
            $baseQuery = PrayerRequest::query();
            $this->applyBranchFilter($baseQuery, 'branch_id');
            $baseQuery->where(function ($query) use ($searchTerm): void {
                $query->where('title', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });

            $total = (clone $baseQuery)->count();

            if ($total > 0) {
                $prayerRequests = $baseQuery
                    ->with($this->searchAllBranches ? ['branch:id,name'] : [])
                    ->select(['id', 'title', 'status', 'created_at', 'branch_id'])
                    ->limit(self::MAX_RESULTS_PER_TYPE)
                    ->get()
                    ->map(fn (PrayerRequest $p): array => [
                        'id' => $p->id,
                        'title' => $p->title,
                        'subtitle' => $p->created_at?->format('M j, Y'),
                        'icon' => 'heart',
                        'branch_id' => $p->branch_id,
                        'branch_name' => $this->searchAllBranches ? $p->branch?->name : null,
                    ]);

                $results['prayer_requests'] = ['items' => $prayerRequests, 'total' => $total];
            }
        }

        return $results;
    }

    /**
     * Get human-readable label for a result type.
     */
    public function getTypeLabel(string $type): string
    {
        return match ($type) {
            'members' => __('Members'),
            'visitors' => __('Visitors'),
            'services' => __('Services'),
            'households' => __('Households'),
            'clusters' => __('Clusters'),
            'equipment' => __('Equipment'),
            'prayer_requests' => __('Prayer Requests'),
            default => ucfirst($type),
        };
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.search.global-search');
    }
}
