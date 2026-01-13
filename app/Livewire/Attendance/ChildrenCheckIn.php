<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\FamilyCheckInService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ChildrenCheckIn extends Component
{
    public Branch $branch;

    public Service $service;

    public ?string $selectedDate = null;

    // Search
    public string $searchQuery = '';

    // Check-in modal
    public bool $showCheckInModal = false;

    public ?string $selectedChildId = null;

    public ?string $selectedGuardianId = null;

    public ?string $generatedSecurityCode = null;

    // Check-out
    public string $checkoutCode = '';

    public bool $showCheckoutModal = false;

    public ?ChildrenCheckinSecurity $checkoutRecord = null;

    public ?string $checkoutError = null;

    // Tabs
    public string $activeTab = 'checkin';

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('create', [Attendance::class, $branch]);
        $this->branch = $branch;
        $this->service = $service;
        $this->selectedDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function searchResults(): Collection
    {
        if (strlen($this->searchQuery) < 2) {
            return collect();
        }

        $search = $this->searchQuery;

        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->children()
            ->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->with('household')
            ->limit(10)
            ->get()
            ->map(fn ($m): array => [
                'id' => $m->id,
                'name' => $m->fullName(),
                'age' => $m->date_of_birth?->age,
                'household_name' => $m->household?->name,
                'photo_url' => $m->photo_url,
                'already_checked_in' => $this->isAlreadyCheckedIn($m->id),
            ]);
    }

    #[Computed]
    public function checkedInChildren(): Collection
    {
        return ChildrenCheckinSecurity::query()
            ->whereHas('attendance', function ($q): void {
                $q->where('service_id', $this->service->id)
                    ->where('date', $this->selectedDate);
            })
            ->with(['child', 'guardian', 'attendance'])
            ->where('is_checked_out', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function checkedOutChildren(): Collection
    {
        return ChildrenCheckinSecurity::query()
            ->whereHas('attendance', function ($q): void {
                $q->where('service_id', $this->service->id)
                    ->where('date', $this->selectedDate);
            })
            ->with(['child', 'guardian'])
            ->where('is_checked_out', true)
            ->orderBy('checked_out_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedChild(): ?Member
    {
        if (! $this->selectedChildId) {
            return null;
        }

        return Member::with('household.members')->find($this->selectedChildId);
    }

    #[Computed]
    public function availableGuardians(): Collection
    {
        $child = $this->selectedChild;
        if (! $child || ! $child->household) {
            return collect();
        }

        return $child->household->members()
            ->adults()
            ->where('id', '!=', $child->id)
            ->get();
    }

    public function openCheckInModal(string $childId): void
    {
        $this->selectedChildId = $childId;
        $this->selectedGuardianId = null;
        $this->generatedSecurityCode = null;
        $this->showCheckInModal = true;

        // Pre-select household head as guardian if available
        $guardians = $this->availableGuardians;
        if ($guardians->isNotEmpty()) {
            $head = $guardians->firstWhere('household_role', 'head');
            $this->selectedGuardianId = $head?->id ?? $guardians->first()->id;
        }
    }

    public function closeCheckInModal(): void
    {
        $this->showCheckInModal = false;
        $this->selectedChildId = null;
        $this->selectedGuardianId = null;
        $this->generatedSecurityCode = null;
    }

    public function checkInChild(): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        $child = $this->selectedChild;
        if (! $child) {
            return;
        }

        $guardian = $this->selectedGuardianId
            ? Member::find($this->selectedGuardianId)
            : null;

        $familyService = app(FamilyCheckInService::class);
        $security = $familyService->checkInChildWithSecurity(
            $child,
            $guardian,
            $this->service,
            $this->branch
        );

        $this->generatedSecurityCode = $security->security_code;
        $this->searchQuery = '';

        unset($this->searchResults);
        unset($this->checkedInChildren);
    }

    public function verifyCheckout(): void
    {
        $this->checkoutError = null;

        if (strlen($this->checkoutCode) !== 6) {
            $this->checkoutError = __('Please enter a 6-digit security code.');

            return;
        }

        $familyService = app(FamilyCheckInService::class);
        $record = $familyService->verifySecurityCode(
            $this->checkoutCode,
            $this->service,
            $this->selectedDate
        );

        if (! $record) {
            $this->checkoutError = __('Invalid security code. Please try again.');

            return;
        }

        $this->checkoutRecord = $record;
        $this->showCheckoutModal = true;
    }

    public function confirmCheckout(): void
    {
        if (!$this->checkoutRecord instanceof \App\Models\Tenant\ChildrenCheckinSecurity) {
            return;
        }

        $this->checkoutRecord->checkOut();

        $this->checkoutCode = '';
        $this->checkoutRecord = null;
        $this->showCheckoutModal = false;

        unset($this->checkedInChildren);
        unset($this->checkedOutChildren);
    }

    public function cancelCheckout(): void
    {
        $this->checkoutCode = '';
        $this->checkoutRecord = null;
        $this->showCheckoutModal = false;
        $this->checkoutError = null;
    }

    protected function isAlreadyCheckedIn(string $memberId): bool
    {
        return Attendance::where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->where('member_id', $memberId)
            ->exists();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.attendance.children-check-in');
    }
}
