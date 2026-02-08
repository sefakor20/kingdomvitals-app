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
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\BranchContextService;
use App\Services\PlanAccessService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public ?string $currentBranchId = null;

    public int $selectedIndex = -1;

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
        unset($this->results);
    }

    /**
     * Reset selection when search term changes.
     */
    public function updatedSearch(): void
    {
        $this->selectedIndex = -1;
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
            $this->selectResult($item['type'], $item['id']);
        }
    }

    /**
     * Get a flat array of all results for keyboard navigation.
     *
     * @return array<int, array{type: string, id: string}>
     */
    private function getFlatResults(): array
    {
        $flat = [];
        foreach ($this->results as $type => $items) {
            foreach ($items as $item) {
                $flat[] = ['type' => $type, 'id' => $item['id']];
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
            fn (string $s) => $s !== $term
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
    public function selectResult(string $type, string $id): void
    {
        $route = match ($type) {
            'members' => route('members.show', ['branch' => $this->currentBranchId, 'member' => $id]),
            'visitors' => route('visitors.show', ['branch' => $this->currentBranchId, 'visitor' => $id]),
            'services' => route('services.show', ['branch' => $this->currentBranchId, 'service' => $id]),
            'households' => route('households.show', ['branch' => $this->currentBranchId, 'household' => $id]),
            'equipment' => route('equipment.show', ['branch' => $this->currentBranchId, 'equipment' => $id]),
            'clusters' => route('clusters.show', ['branch' => $this->currentBranchId, 'cluster' => $id]),
            'prayer_requests' => route('prayer-requests.show', ['branch' => $this->currentBranchId, 'prayerRequest' => $id]),
            default => route('dashboard'),
        };

        $this->saveRecentSearch($this->search);
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
     * Get search results grouped by type.
     *
     * @return array<string, Collection>
     */
    #[Computed]
    public function results(): array
    {
        if (strlen($this->search) < 2 || ! $this->currentBranchId) {
            return [];
        }

        $planAccess = app(PlanAccessService::class);
        $results = [];
        $searchTerm = '%'.$this->search.'%';

        // Members
        if ($planAccess->hasModule(PlanModule::Members)) {
            $members = Member::query()
                ->where('primary_branch_id', $this->currentBranchId)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('membership_number', 'like', $searchTerm);
                })
                ->select(['id', 'first_name', 'last_name', 'membership_number', 'email'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Member $m) => [
                    'id' => $m->id,
                    'title' => $m->fullName(),
                    'subtitle' => $m->membership_number ?? $m->email,
                    'icon' => 'user',
                ]);

            if ($members->isNotEmpty()) {
                $results['members'] = $members;
            }
        }

        // Visitors
        if ($planAccess->hasModule(PlanModule::Visitors)) {
            $visitors = Visitor::query()
                ->where('branch_id', $this->currentBranchId)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm);
                })
                ->select(['id', 'first_name', 'last_name', 'email', 'visit_date'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Visitor $v) => [
                    'id' => $v->id,
                    'title' => $v->fullName(),
                    'subtitle' => $v->visit_date?->format('M j, Y'),
                    'icon' => 'user-plus',
                ]);

            if ($visitors->isNotEmpty()) {
                $results['visitors'] = $visitors;
            }
        }

        // Services
        if ($planAccess->hasModule(PlanModule::Services)) {
            $services = Service::query()
                ->where('branch_id', $this->currentBranchId)
                ->where('name', 'like', $searchTerm)
                ->select(['id', 'name', 'day_of_week', 'time'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Service $s) => [
                    'id' => $s->id,
                    'title' => $s->name,
                    'subtitle' => $s->day_of_week !== null ? now()->startOfWeek()->addDays($s->day_of_week)->format('l') : null,
                    'icon' => 'calendar',
                ]);

            if ($services->isNotEmpty()) {
                $results['services'] = $services;
            }
        }

        // Households
        if ($planAccess->hasModule(PlanModule::Households)) {
            $households = Household::query()
                ->where('branch_id', $this->currentBranchId)
                ->where('name', 'like', $searchTerm)
                ->select(['id', 'name', 'address'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Household $h) => [
                    'id' => $h->id,
                    'title' => $h->name,
                    'subtitle' => $h->address,
                    'icon' => 'home',
                ]);

            if ($households->isNotEmpty()) {
                $results['households'] = $households;
            }
        }

        // Clusters
        if ($planAccess->hasModule(PlanModule::Clusters)) {
            $clusters = Cluster::query()
                ->where('branch_id', $this->currentBranchId)
                ->where('name', 'like', $searchTerm)
                ->select(['id', 'name', 'description'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Cluster $c) => [
                    'id' => $c->id,
                    'title' => $c->name,
                    'subtitle' => $c->description ? \Illuminate\Support\Str::limit($c->description, 50) : null,
                    'icon' => 'users',
                ]);

            if ($clusters->isNotEmpty()) {
                $results['clusters'] = $clusters;
            }
        }

        // Equipment
        if ($planAccess->hasModule(PlanModule::Equipment)) {
            $equipment = Equipment::query()
                ->where('branch_id', $this->currentBranchId)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', $searchTerm)
                        ->orWhere('serial_number', 'like', $searchTerm);
                })
                ->select(['id', 'name', 'serial_number', 'condition'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (Equipment $e) => [
                    'id' => $e->id,
                    'title' => $e->name,
                    'subtitle' => $e->serial_number,
                    'icon' => 'wrench',
                ]);

            if ($equipment->isNotEmpty()) {
                $results['equipment'] = $equipment;
            }
        }

        // Prayer Requests
        if ($planAccess->hasModule(PlanModule::PrayerRequests)) {
            $prayerRequests = PrayerRequest::query()
                ->where('branch_id', $this->currentBranchId)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('title', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm);
                })
                ->select(['id', 'title', 'status', 'created_at'])
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get()
                ->map(fn (PrayerRequest $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'subtitle' => $p->created_at?->format('M j, Y'),
                    'icon' => 'heart',
                ]);

            if ($prayerRequests->isNotEmpty()) {
                $results['prayer_requests'] = $prayerRequests;
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
