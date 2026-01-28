<?php

declare(strict_types=1);

namespace App\Livewire\Members;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.print')]
class MemberCardsBulkPrint extends Component
{
    public Branch $branch;

    /** @var array<string> */
    public array $memberIds = [];

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Member::class, $branch]);
        $this->branch = $branch;

        // Parse member IDs from query string
        $ids = request()->query('ids', '');
        $this->memberIds = array_filter(explode(',', (string) $ids));
    }

    /**
     * @return Collection<int, Member>
     */
    #[Computed]
    public function members(): Collection
    {
        if (empty($this->memberIds)) {
            return collect();
        }

        return Member::where('primary_branch_id', $this->branch->id)
            ->whereIn('id', $this->memberIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function qrCodes(): array
    {
        $qrCodeService = app(QrCodeService::class);
        $qrCodes = [];

        foreach ($this->members as $member) {
            $token = $member->getOrGenerateQrToken();
            $qrCodes[$member->id] = $qrCodeService->generateQrCodeSvg($token, 64);
        }

        return $qrCodes;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.members.member-cards-bulk-print');
    }
}
