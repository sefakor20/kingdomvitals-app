<?php

declare(strict_types=1);

namespace App\Livewire\Members;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\QrCodeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.print')]
class MemberQrPrint extends Component
{
    public Branch $branch;

    public Member $member;

    public function mount(Branch $branch, Member $member): void
    {
        $this->authorize('view', $member);
        $this->branch = $branch;
        $this->member = $member;
    }

    #[Computed]
    public function qrCodeSvg(): string
    {
        $token = $this->member->getOrGenerateQrToken();

        return app(QrCodeService::class)->generateQrCodeSvg($token, 64);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.members.member-qr-print');
    }
}
