<?php

namespace App\Livewire\Services;

use App\Enums\CheckInMethod;
use App\Enums\ServiceType;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ServiceShow extends Component
{
    public Branch $branch;

    public Service $service;

    public bool $editing = false;

    // Form fields
    public string $name = '';

    public ?int $day_of_week = null;

    public string $time = '';

    public string $service_type = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    // Delete modal
    public bool $showDeleteModal = false;

    // Attendance form fields
    public bool $showAddAttendanceModal = false;

    public ?string $attendanceMemberId = null;

    public ?string $attendanceDate = null;

    public ?string $attendanceCheckInTime = null;

    public ?string $attendanceCheckOutTime = null;

    public string $attendanceCheckInMethod = 'manual';

    public ?string $attendanceNotes = null;

    public ?string $editingAttendanceId = null;

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('view', $service);
        $this->branch = $branch;
        $this->service = $service;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->service);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->service);
    }

    #[Computed]
    public function serviceTypes(): array
    {
        return ServiceType::cases();
    }

    #[Computed]
    public function attendanceCount(): int
    {
        return $this->service->attendance()->count();
    }

    #[Computed]
    public function donationCount(): int
    {
        return $this->service->donations()->count();
    }

    #[Computed]
    public function attendanceRecords(): Collection
    {
        return $this->service->attendance()
            ->with('member')
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();
    }

    #[Computed]
    public function availableMembers(): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function canManageAttendance(): bool
    {
        return auth()->user()->can('create', [Attendance::class, $this->branch]);
    }

    #[Computed]
    public function checkInMethods(): array
    {
        return CheckInMethod::cases();
    }

    public function getDayName(?int $day): string
    {
        if ($day === null) {
            return '-';
        }

        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day] ?? '-';
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time' => ['required', 'date_format:H:i'],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->service);

        $this->fill([
            'name' => $this->service->name,
            'day_of_week' => $this->service->day_of_week,
            'time' => substr($this->service->time, 0, 5), // Format as H:i
            'service_type' => $this->service->service_type->value,
            'capacity' => $this->service->capacity,
            'is_active' => $this->service->is_active,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->service);
        $validated = $this->validate();

        // Convert empty capacity to null
        if (isset($validated['capacity']) && $validated['capacity'] === '') {
            $validated['capacity'] = null;
        }

        $this->service->update($validated);
        $this->service->refresh();

        $this->editing = false;
        $this->dispatch('service-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->service);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->service);
        $this->service->delete();
        $this->dispatch('service-deleted');
        $this->redirect(route('services.index', $this->branch), navigate: true);
    }

    // Attendance management methods
    public function openAddAttendanceModal(): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        $this->resetAttendanceForm();
        $this->attendanceDate = now()->format('Y-m-d');
        $this->attendanceCheckInTime = now()->format('H:i');
        $this->showAddAttendanceModal = true;
    }

    public function closeAddAttendanceModal(): void
    {
        $this->showAddAttendanceModal = false;
        $this->resetAttendanceForm();
    }

    public function resetAttendanceForm(): void
    {
        $this->reset([
            'attendanceMemberId',
            'attendanceDate',
            'attendanceCheckInTime',
            'attendanceCheckOutTime',
            'attendanceCheckInMethod',
            'attendanceNotes',
            'editingAttendanceId',
        ]);
        $this->attendanceCheckInMethod = 'manual';
        $this->resetValidation();
    }

    protected function attendanceRules(): array
    {
        return [
            'attendanceMemberId' => ['required', 'uuid', 'exists:members,id'],
            'attendanceDate' => ['required', 'date'],
            'attendanceCheckInTime' => ['required', 'date_format:H:i'],
            'attendanceCheckOutTime' => ['nullable', 'date_format:H:i'],
            'attendanceCheckInMethod' => ['required', Rule::enum(CheckInMethod::class)],
            'attendanceNotes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function addAttendance(): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        $this->validate($this->attendanceRules());

        // Verify member belongs to same branch and is active
        $member = Member::where('id', $this->attendanceMemberId)
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->first();

        if (! $member) {
            $this->addError('attendanceMemberId', __('Invalid member selected.'));

            return;
        }

        // Check for duplicate attendance
        $exists = Attendance::where('service_id', $this->service->id)
            ->where('date', $this->attendanceDate)
            ->where('member_id', $this->attendanceMemberId)
            ->exists();

        if ($exists) {
            $this->addError('attendanceMemberId', __('This member already has an attendance record for this date.'));

            return;
        }

        Attendance::create([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'member_id' => $this->attendanceMemberId,
            'date' => $this->attendanceDate,
            'check_in_time' => $this->attendanceCheckInTime,
            'check_out_time' => $this->attendanceCheckOutTime ?: null,
            'check_in_method' => $this->attendanceCheckInMethod,
            'notes' => $this->attendanceNotes ?: null,
        ]);

        $this->service->refresh();
        $this->closeAddAttendanceModal();
        $this->dispatch('attendance-added');
    }

    public function editAttendance(string $id): void
    {
        $attendance = Attendance::where('id', $id)
            ->where('service_id', $this->service->id)
            ->firstOrFail();

        $this->authorize('update', $attendance);

        $this->editingAttendanceId = $id;
        $this->attendanceMemberId = $attendance->member_id;
        $this->attendanceDate = $attendance->date->format('Y-m-d');
        // Strip seconds from time fields to match H:i validation format
        $this->attendanceCheckInTime = $attendance->check_in_time ? substr($attendance->check_in_time, 0, 5) : null;
        $this->attendanceCheckOutTime = $attendance->check_out_time ? substr($attendance->check_out_time, 0, 5) : null;
        $this->attendanceCheckInMethod = $attendance->check_in_method->value;
        $this->attendanceNotes = $attendance->notes;

        $this->showAddAttendanceModal = true;
    }

    public function updateAttendance(): void
    {
        $attendance = Attendance::where('id', $this->editingAttendanceId)
            ->where('service_id', $this->service->id)
            ->firstOrFail();

        $this->authorize('update', $attendance);

        $this->validate($this->attendanceRules());

        // Check for duplicate attendance (excluding current record)
        $exists = Attendance::where('service_id', $this->service->id)
            ->where('date', $this->attendanceDate)
            ->where('member_id', $this->attendanceMemberId)
            ->where('id', '!=', $this->editingAttendanceId)
            ->exists();

        if ($exists) {
            $this->addError('attendanceMemberId', __('This member already has an attendance record for this date.'));

            return;
        }

        $attendance->update([
            'member_id' => $this->attendanceMemberId,
            'date' => $this->attendanceDate,
            'check_in_time' => $this->attendanceCheckInTime,
            'check_out_time' => $this->attendanceCheckOutTime ?: null,
            'check_in_method' => $this->attendanceCheckInMethod,
            'notes' => $this->attendanceNotes ?: null,
        ]);

        $this->service->refresh();
        $this->closeAddAttendanceModal();
        $this->dispatch('attendance-updated');
    }

    public function saveAttendance(): void
    {
        if ($this->editingAttendanceId) {
            $this->updateAttendance();
        } else {
            $this->addAttendance();
        }
    }

    public function deleteAttendance(string $id): void
    {
        $attendance = Attendance::where('id', $id)
            ->where('service_id', $this->service->id)
            ->firstOrFail();

        $this->authorize('delete', $attendance);

        $attendance->delete();
        $this->service->refresh();
        $this->dispatch('attendance-deleted');
    }

    public function render()
    {
        return view('livewire.services.service-show');
    }
}
