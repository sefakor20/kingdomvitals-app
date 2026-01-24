<?php

namespace App\Livewire\DutyRosters;

use App\Enums\DutyRosterRoleType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\DutyRosterPool;
use App\Models\Tenant\Service;
use App\Services\DutyRosterGenerationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DutyRosterGenerationWizard extends Component
{
    public Branch $branch;

    // Wizard state
    public int $step = 1;

    // Step 1: Date selection
    public ?string $service_id = null;

    public array $days_of_week = [];

    public string $start_date = '';

    public string $end_date = '';

    // Step 2: Pool selection
    public ?string $preacher_pool_id = null;

    public ?string $liturgist_pool_id = null;

    public ?string $reader_pool_id = null;

    // Step 3: Preview
    public array $preview = [];

    public bool $skipExisting = true;

    // Generation results
    public int $generatedCount = 0;

    public bool $isGenerating = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('generate', [DutyRoster::class, $branch]);
        $this->branch = $branch;
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonth()->format('Y-m-d');
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->whereNotNull('day_of_week')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function preacherPools(): Collection
    {
        return DutyRosterPool::where('branch_id', $this->branch->id)
            ->where('role_type', DutyRosterRoleType::Preacher)
            ->where('is_active', true)
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function liturgistPools(): Collection
    {
        return DutyRosterPool::where('branch_id', $this->branch->id)
            ->where('role_type', DutyRosterRoleType::Liturgist)
            ->where('is_active', true)
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function readerPools(): Collection
    {
        return DutyRosterPool::where('branch_id', $this->branch->id)
            ->where('role_type', DutyRosterRoleType::Reader)
            ->where('is_active', true)
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function daysOfWeekOptions(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'service_id' => ['nullable', 'uuid', 'exists:services,id'],
                'days_of_week' => ['required_without:service_id', 'array'],
                'days_of_week.*' => ['integer', 'min:0', 'max:6'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after:start_date'],
            ],
            2 => [
                'preacher_pool_id' => ['nullable', 'uuid', 'exists:duty_roster_pools,id'],
                'liturgist_pool_id' => ['nullable', 'uuid', 'exists:duty_roster_pools,id'],
                'reader_pool_id' => ['nullable', 'uuid', 'exists:duty_roster_pools,id'],
            ],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));

        if ($this->step === 1 && empty($this->service_id) && empty($this->days_of_week)) {
            $this->addError('service_id', 'Please select a service or at least one day of the week.');

            return;
        }

        if ($this->step === 2) {
            // Generate preview
            $this->generatePreview();
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    private function generatePreview(): void
    {
        $service = app(DutyRosterGenerationService::class);

        $config = [
            'service_id' => $this->service_id,
            'days_of_week' => array_map('intval', $this->days_of_week),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'preacher_pool_id' => $this->preacher_pool_id,
            'liturgist_pool_id' => $this->liturgist_pool_id,
            'reader_pool_id' => $this->reader_pool_id,
        ];

        $this->preview = $service->previewGeneration($this->branch, $config);
    }

    public function generate(): void
    {
        $this->authorize('generate', [DutyRoster::class, $this->branch]);

        $this->isGenerating = true;

        $service = app(DutyRosterGenerationService::class);

        $config = [
            'service_id' => $this->service_id,
            'days_of_week' => array_map('intval', $this->days_of_week),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'preacher_pool_id' => $this->preacher_pool_id,
            'liturgist_pool_id' => $this->liturgist_pool_id,
            'reader_pool_id' => $this->reader_pool_id,
            'skip_existing' => $this->skipExisting,
        ];

        $rosters = $service->generateRosters($this->branch, $config, auth()->id());

        $this->generatedCount = $rosters->count();
        $this->isGenerating = false;
        $this->step = 4; // Success step
    }

    public function finish(): mixed
    {
        return $this->redirect(route('duty-rosters.index', $this->branch), navigate: true);
    }

    public function startOver(): void
    {
        $this->reset([
            'step', 'service_id', 'days_of_week', 'preacher_pool_id',
            'liturgist_pool_id', 'reader_pool_id', 'preview', 'generatedCount',
        ]);
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonth()->format('Y-m-d');
        $this->skipExisting = true;
        $this->step = 1;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.duty-roster-generation-wizard');
    }
}
