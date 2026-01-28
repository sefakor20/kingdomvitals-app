<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\FamilyCheckInService;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LiveCheckIn extends Component
{
    public Branch $branch;

    public Service $service;

    public string $searchQuery = '';

    public ?string $selectedDate = null;

    public ?string $lastCheckedInName = null;

    // Tab state
    public string $activeTab = 'search';

    // QR scanning
    public bool $isScanning = false;

    public ?string $qrError = null;

    // Family check-in
    public bool $showFamilyModal = false;

    public ?string $selectedHouseholdId = null;

    /** @var array<string> */
    public array $selectedFamilyMembers = [];

    public string $familySearchQuery = '';

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('create', [Attendance::class, $branch]);
        $this->branch = $branch;
        $this->service = $service;
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->qrError = null;

        if ($tab !== 'qr') {
            $this->isScanning = false;
        }
    }

    #[Computed]
    public function searchResults(): Collection
    {
        if (strlen($this->searchQuery) < 2) {
            return collect();
        }

        $search = $this->searchQuery;

        // Get matching members
        $members = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($m): array => [
                'id' => $m->id,
                'name' => $m->fullName(),
                'type' => 'member',
                'photo_url' => $m->photo_url,
                'already_checked_in' => $this->isAlreadyCheckedIn('member', $m->id),
            ]);

        // Get matching visitors
        $visitors = Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereNull('converted_member_id')
            ->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($v): array => [
                'id' => $v->id,
                'name' => $v->fullName(),
                'type' => 'visitor',
                'photo_url' => null,
                'already_checked_in' => $this->isAlreadyCheckedIn('visitor', $v->id),
            ]);

        return $members->concat($visitors)->sortBy('name')->take(12);
    }

    #[Computed]
    public function recentCheckIns(): Collection
    {
        return Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->with(['member', 'visitor'])
            ->orderBy('check_in_time', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($a): array => [
                'id' => $a->id,
                'name' => $a->member?->fullName() ?? $a->visitor?->fullName() ?? 'Unknown',
                'type' => $a->member_id ? 'member' : 'visitor',
                'photo_url' => $a->member?->photo_url,
                'check_in_time' => $a->check_in_time ? substr($a->check_in_time, 0, 5) : '-',
                'check_out_time' => $a->check_out_time ? substr($a->check_out_time, 0, 5) : null,
                'is_checked_out' => $a->check_out_time !== null,
            ]);
    }

    #[Computed]
    public function todayStats(): array
    {
        $attendance = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate);

        return [
            'total' => $attendance->count(),
            'members' => (clone $attendance)->whereNotNull('member_id')->count(),
            'visitors' => (clone $attendance)->whereNotNull('visitor_id')->count(),
            'checked_out' => (clone $attendance)->whereNotNull('check_out_time')->count(),
        ];
    }

    #[Computed]
    public function householdSearchResults(): Collection
    {
        if (strlen($this->familySearchQuery) < 2) {
            return collect();
        }

        return Household::query()
            ->where('branch_id', $this->branch->id)
            ->where('name', 'like', "%{$this->familySearchQuery}%")
            ->withCount('members')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedHousehold(): ?Household
    {
        if (! $this->selectedHouseholdId) {
            return null;
        }

        return Household::with(['members' => function ($query): void {
            $query->where('status', 'active')
                ->orderByRaw("CASE WHEN household_role = 'head' THEN 1 WHEN household_role = 'spouse' THEN 2 WHEN household_role = 'child' THEN 3 ELSE 4 END")
                ->orderBy('first_name');
        }])->find($this->selectedHouseholdId);
    }

    public function checkIn(string $id, string $type): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        // Check if already checked in
        if ($this->isAlreadyCheckedIn($type, $id)) {
            $this->dispatch('already-checked-in');

            return;
        }

        $memberId = null;
        $visitorId = null;
        $name = '';

        if ($type === 'member') {
            $member = Member::where('id', $id)
                ->where('primary_branch_id', $this->branch->id)
                ->where('status', 'active')
                ->first();

            if (! $member) {
                $this->addError('checkIn', __('Invalid member selected.'));

                return;
            }

            $memberId = $member->id;
            $name = $member->fullName();
        } else {
            $visitor = Visitor::where('id', $id)
                ->where('branch_id', $this->branch->id)
                ->whereNull('converted_member_id')
                ->first();

            if (! $visitor) {
                $this->addError('checkIn', __('Invalid visitor selected.'));

                return;
            }

            $visitorId = $visitor->id;
            $name = $visitor->fullName();
        }

        Attendance::create([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'member_id' => $memberId,
            'visitor_id' => $visitorId,
            'date' => $this->selectedDate,
            'check_in_time' => now()->format('H:i'),
            'check_in_method' => CheckInMethod::Kiosk,
        ]);

        // Clear search and show feedback
        $this->searchQuery = '';
        $this->lastCheckedInName = $name;

        // Reset computed properties
        unset($this->searchResults);
        unset($this->recentCheckIns);
        unset($this->todayStats);

        $this->dispatch('check-in-success', name: $name);
    }

    #[On('qr-scanned')]
    public function processQrCode(string $code): void
    {
        $this->qrError = null;

        $qrService = app(QrCodeService::class);
        $member = $qrService->validateToken($code);

        if (! $member) {
            $this->qrError = __('Invalid QR code. Please try again.');
            $this->dispatch('qr-error');

            return;
        }

        if ($member->primary_branch_id !== $this->branch->id) {
            $this->qrError = __('This member belongs to a different branch.');
            $this->dispatch('qr-error');

            return;
        }

        if ($member->status->value !== 'active') {
            $this->qrError = __('This member is not active.');
            $this->dispatch('qr-error');

            return;
        }

        if ($this->isAlreadyCheckedIn('member', $member->id)) {
            $this->qrError = __(':name is already checked in.', ['name' => $member->fullName()]);
            $this->dispatch('already-checked-in');

            return;
        }

        // Create attendance with QR method
        Attendance::create([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'member_id' => $member->id,
            'date' => $this->selectedDate,
            'check_in_time' => now()->format('H:i'),
            'check_in_method' => CheckInMethod::Qr,
        ]);

        $this->lastCheckedInName = $member->fullName();

        unset($this->recentCheckIns);
        unset($this->todayStats);

        $this->dispatch('check-in-success', name: $member->fullName());
    }

    public function openFamilyModal(string $householdId): void
    {
        $this->selectedHouseholdId = $householdId;
        $this->selectedFamilyMembers = [];
        $this->showFamilyModal = true;

        // Pre-select all members that aren't already checked in
        $household = $this->selectedHousehold;
        if ($household) {
            foreach ($household->members as $member) {
                if (! $this->isAlreadyCheckedIn('member', $member->id)) {
                    $this->selectedFamilyMembers[] = $member->id;
                }
            }
        }
    }

    public function closeFamilyModal(): void
    {
        $this->showFamilyModal = false;
        $this->selectedHouseholdId = null;
        $this->selectedFamilyMembers = [];
        $this->familySearchQuery = '';
    }

    public function toggleFamilyMember(string $memberId): void
    {
        if (in_array($memberId, $this->selectedFamilyMembers)) {
            $this->selectedFamilyMembers = array_diff($this->selectedFamilyMembers, [$memberId]);
        } else {
            $this->selectedFamilyMembers[] = $memberId;
        }
    }

    public function checkInSelectedFamily(): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        if ($this->selectedFamilyMembers === [] || ! $this->selectedHousehold) {
            return;
        }

        $familyService = app(FamilyCheckInService::class);
        $checkedIn = $familyService->checkInFamily(
            $this->selectedHousehold,
            $this->service,
            $this->branch,
            $this->selectedFamilyMembers
        );

        $count = $checkedIn->count();

        if ($count > 0) {
            $this->dispatch('family-check-in-success', count: $count);
        }

        $this->closeFamilyModal();

        unset($this->recentCheckIns);
        unset($this->todayStats);
        unset($this->householdSearchResults);
    }

    protected function isAlreadyCheckedIn(string $type, string $id): bool
    {
        $query = Attendance::where('service_id', $this->service->id)
            ->where('date', $this->selectedDate);

        if ($type === 'member') {
            return $query->where('member_id', $id)->exists();
        }

        return $query->where('visitor_id', $id)->exists();
    }

    public function startScanning(): void
    {
        $this->isScanning = true;
        $this->qrError = null;
    }

    public function stopScanning(): void
    {
        $this->isScanning = false;
    }

    public function checkOut(string $attendanceId): void
    {
        $this->authorize('update', [Attendance::class, $this->branch]);

        $attendance = Attendance::where('id', $attendanceId)
            ->where('service_id', $this->service->id)
            ->where('branch_id', $this->branch->id)
            ->whereNull('check_out_time')
            ->first();

        if (! $attendance) {
            return;
        }

        $attendance->update(['check_out_time' => now()->format('H:i')]);

        $name = $attendance->member?->fullName() ?? $attendance->visitor?->fullName() ?? 'Unknown';

        unset($this->recentCheckIns);
        unset($this->todayStats);

        $this->dispatch('check-out-success', name: $name);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.attendance.live-check-in');
    }
}
