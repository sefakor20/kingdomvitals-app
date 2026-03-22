<?php

declare(strict_types=1);

namespace App\Livewire\Giving;

use App\Enums\RiskLevel;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\PledgePrediction;
use App\Services\AI\GivingCapacityService;
use App\Services\AI\PledgePredictionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class GivingIntelligenceDashboard extends Component
{
    use WithPagination;

    public Branch $branch;

    #[Url]
    public string $view = 'capacity'; // 'capacity' or 'pledges'

    #[Url]
    public string $riskFilter = '';

    #[Url]
    public string $search = '';

    public function mount(Branch $branch): void
    {
        $this->authorize('view', $branch);
        $this->branch = $branch;
    }

    /**
     * Switch between capacity and pledge views.
     */
    public function switchView(string $view): void
    {
        $this->view = $view;
        $this->resetPage();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->riskFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Run capacity analysis for the branch.
     */
    public function runCapacityAnalysis(): void
    {
        $service = app(GivingCapacityService::class);

        if (! $service->isEnabled()) {
            $this->dispatch('notify', type: 'error', message: __('Giving capacity feature is disabled.'));

            return;
        }

        $assessments = $service->assessForBranch($this->branch);
        $saved = $service->saveAssessments($assessments);

        $this->dispatch('notify', type: 'success', message: __('Analyzed :count members.', ['count' => $saved]));
    }

    /**
     * Run pledge prediction for the branch.
     */
    public function runPledgePrediction(): void
    {
        $service = app(PledgePredictionService::class);

        if (! $service->isEnabled()) {
            $this->dispatch('notify', type: 'error', message: __('Pledge prediction feature is disabled.'));

            return;
        }

        $predictions = $service->predictForBranch($this->branch);
        $saved = $service->savePredictions($predictions, $this->branch);

        $this->dispatch('notify', type: 'success', message: __('Generated :count predictions.', ['count' => $saved]));
    }

    // ============================================
    // GIVING CAPACITY
    // ============================================

    #[Computed]
    public function highPotentialMembers(): LengthAwarePaginator
    {
        $query = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('giving_capacity_score')
            ->where('giving_capacity_score', '<', 60)
            ->where('giving_potential_gap', '>', 0)
            ->orderByDesc('giving_potential_gap');

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%');
            });
        }

        return $query->paginate(15);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function capacityStats(): array
    {
        $members = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('giving_capacity_score')
            ->get();

        $totalPotentialGap = $members->sum('giving_potential_gap');
        $avgCapacityScore = $members->count() > 0 ? $members->avg('giving_capacity_score') : 0;

        // Distribution
        $underUtilized = $members->where('giving_capacity_score', '<', 40)->count();
        $moderatelyUtilized = $members->filter(fn ($m) => $m->giving_capacity_score >= 40 && $m->giving_capacity_score < 70)->count();
        $wellUtilized = $members->where('giving_capacity_score', '>=', 70)->count();

        return [
            'total_analyzed' => $members->count(),
            'total_potential_gap' => $totalPotentialGap,
            'avg_capacity_score' => round($avgCapacityScore, 1),
            'under_utilized' => $underUtilized,
            'moderately_utilized' => $moderatelyUtilized,
            'well_utilized' => $wellUtilized,
        ];
    }

    // ============================================
    // PLEDGE PREDICTIONS
    // ============================================

    #[Computed]
    public function atRiskPledges(): LengthAwarePaginator
    {
        $query = PledgePrediction::query()
            ->where('branch_id', $this->branch->id)
            ->with(['pledge', 'member'])
            ->orderBy('risk_level')
            ->orderBy('fulfillment_probability');

        if ($this->riskFilter !== '') {
            $riskLevel = RiskLevel::tryFrom($this->riskFilter);
            if ($riskLevel) {
                $query->where('risk_level', $riskLevel);
            }
        }

        if ($this->search !== '') {
            $query->whereHas('member', function ($q): void {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%');
            });
        }

        return $query->paginate(15);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function pledgeStats(): array
    {
        $predictions = PledgePrediction::where('branch_id', $this->branch->id)->get();

        return [
            'total' => $predictions->count(),
            'high_risk' => $predictions->where('risk_level', RiskLevel::High)->count(),
            'medium_risk' => $predictions->where('risk_level', RiskLevel::Medium)->count(),
            'low_risk' => $predictions->where('risk_level', RiskLevel::Low)->count(),
            'avg_probability' => $predictions->count() > 0
                ? round($predictions->avg('fulfillment_probability'), 1)
                : 0,
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableRiskLevels(): array
    {
        return collect(RiskLevel::cases())
            ->mapWithKeys(fn (RiskLevel $level) => [$level->value => $level->label()])
            ->all();
    }

    #[Computed]
    public function capacityFeatureEnabled(): bool
    {
        return app(GivingCapacityService::class)->isEnabled();
    }

    #[Computed]
    public function pledgeFeatureEnabled(): bool
    {
        return app(PledgePredictionService::class)->isEnabled();
    }

    public function render(): View
    {
        return view('livewire.giving.giving-intelligence-dashboard');
    }
}
