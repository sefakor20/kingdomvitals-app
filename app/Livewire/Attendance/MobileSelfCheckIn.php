<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class MobileSelfCheckIn extends Component
{
    public ?Member $member = null;

    public string $token = '';

    public ?string $selectedServiceId = null;

    public bool $showSuccess = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(?string $token = null): void
    {
        $this->token = $token ?? '';

        if ($this->token !== '' && $this->token !== '0') {
            $qrService = app(QrCodeService::class);
            $this->member = $qrService->validateToken($this->token);
        }
    }

    #[Computed]
    public function qrCodeSvg(): ?string
    {
        if (! $this->member instanceof \App\Models\Tenant\Member) {
            return null;
        }

        $token = $this->member->getOrGenerateQrToken();

        return app(QrCodeService::class)->generateQrCodeSvg($token, 240);
    }

    #[Computed]
    public function availableServices(): Collection
    {
        if (! $this->member || ! $this->member->primaryBranch) {
            return collect();
        }

        $today = now()->dayOfWeekIso;

        return Service::query()
            ->where('branch_id', $this->member->primary_branch_id)
            ->where('is_active', true)
            ->where('day_of_week', $today)
            ->get()
            ->map(function ($service): array {
                $isCheckedIn = Attendance::where('service_id', $service->id)
                    ->where('member_id', $this->member->id)
                    ->where('date', now()->toDateString())
                    ->exists();

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'time' => $service->time,
                    'is_checked_in' => $isCheckedIn,
                ];
            });
    }

    public function selfCheckIn(): void
    {
        $this->errorMessage = null;
        $this->showSuccess = false;

        if (! $this->member instanceof \App\Models\Tenant\Member) {
            $this->errorMessage = __('Invalid member token.');

            return;
        }

        if (! $this->selectedServiceId) {
            $this->errorMessage = __('Please select a service.');

            return;
        }

        $service = Service::where('id', $this->selectedServiceId)
            ->where('branch_id', $this->member->primary_branch_id)
            ->where('is_active', true)
            ->first();

        if (! $service) {
            $this->errorMessage = __('Invalid service selected.');

            return;
        }

        // Check if already checked in
        $existing = Attendance::where('service_id', $service->id)
            ->where('member_id', $this->member->id)
            ->where('date', now()->toDateString())
            ->exists();

        if ($existing) {
            $this->errorMessage = __('You are already checked in for this service.');

            return;
        }

        Attendance::create([
            'service_id' => $service->id,
            'branch_id' => $this->member->primary_branch_id,
            'member_id' => $this->member->id,
            'date' => now()->toDateString(),
            'check_in_time' => now()->format('H:i'),
            'check_in_method' => CheckInMethod::Mobile,
        ]);

        $this->showSuccess = true;
        $this->successMessage = __('You have been checked in to :service!', ['service' => $service->name]);
        $this->selectedServiceId = null;

        unset($this->availableServices);
    }

    public function regenerateQrCode(): void
    {
        if (! $this->member instanceof \App\Models\Tenant\Member) {
            return;
        }

        $qrService = app(QrCodeService::class);
        $this->token = $qrService->regenerateToken($this->member);
        $this->member->refresh();

        unset($this->qrCodeSvg);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.attendance.mobile-self-check-in');
    }
}
