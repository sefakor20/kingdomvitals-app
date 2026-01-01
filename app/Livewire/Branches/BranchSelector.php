<?php

namespace App\Livewire\Branches;

use App\Models\Tenant\Branch;
use App\Services\BranchContextService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class BranchSelector extends Component
{
    #[Session(key: 'current_branch_id')]
    public ?string $currentBranchId = null;

    public function mount(BranchContextService $branchContext): void
    {
        if (! $this->currentBranchId) {
            $this->currentBranchId = $branchContext->getDefaultBranchId();
        }
    }

    #[Computed]
    public function branches(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return $user->accessibleBranches()
            ->where('status', 'active')
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function currentBranch(): ?Branch
    {
        if (! $this->currentBranchId) {
            return null;
        }

        return $this->branches->firstWhere('id', $this->currentBranchId);
    }

    public function switchBranch(string $branchId): void
    {
        if (! $this->branches->contains('id', $branchId)) {
            return;
        }

        // Update both component state AND explicitly persist to session
        $this->currentBranchId = $branchId;
        app(BranchContextService::class)->setCurrentBranch($branchId);

        // Check if we need to navigate to a new route
        $currentRoute = request()->route()?->getName();
        $branchScopedRoutes = [
            'members.index',
            'members.show',
            'clusters.index',
            'clusters.show',
            'services.index',
            'services.show',
            'branches.users.index',
        ];

        if ($currentRoute && in_array($currentRoute, $branchScopedRoutes)) {
            $newBranch = Branch::find($branchId);

            // For detail routes, redirect to index (item may not exist in new branch)
            $indexRoute = match ($currentRoute) {
                'members.index', 'members.show' => 'members.index',
                'clusters.index', 'clusters.show' => 'clusters.index',
                'services.index', 'services.show' => 'services.index',
                'branches.users.index' => 'branches.users.index',
                default => null,
            };

            if ($indexRoute && $newBranch) {
                // Use full page redirect to avoid Livewire snapshot conflicts
                $this->redirect(route($indexRoute, $newBranch), navigate: false);

                return;
            }
        }

        // Only dispatch event when NOT redirecting (e.g., on Dashboard, Branches list)
        $this->dispatch('branch-switched', branchId: $branchId);
    }

    public function render()
    {
        return view('livewire.branches.branch-selector');
    }
}
