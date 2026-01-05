<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Membership;

use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class MemberDirectory extends Component
{
    use HasReportExport, WithPagination;

    public Branch $branch;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $gender = '';

    #[Url]
    public string $cluster = '';

    public string $sortBy = 'first_name';

    public string $sortDirection = 'asc';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedGender(): void
    {
        $this->resetPage();
    }

    public function updatedCluster(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'gender', 'cluster']);
        $this->resetPage();
    }

    #[Computed]
    public function members(): LengthAwarePaginator
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->gender, fn ($query) => $query->where('gender', $this->gender))
            ->when($this->cluster, fn ($query) => $query->whereHas('clusters', fn ($q) => $q->where('cluster_id', $this->cluster)))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);
    }

    #[Computed]
    public function statuses(): array
    {
        return MembershipStatus::cases();
    }

    #[Computed]
    public function genders(): array
    {
        return Gender::cases();
    }

    #[Computed]
    public function clusters(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->status !== '' || $this->gender !== '' || $this->cluster !== '';
    }

    #[Computed]
    public function totalCount(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)->count();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Gender', 'Status', 'Joined Date', 'City'];
        $filename = $this->generateFilename('member-directory', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Name', 'Email', 'Phone', 'Gender', 'Status', 'Joined Date', 'City'];
        $filename = $this->generateFilename('member-directory', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->gender, fn ($query) => $query->where('gender', $this->gender))
            ->when($this->cluster, fn ($query) => $query->whereHas('clusters', fn ($q) => $q->where('cluster_id', $this->cluster)))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get()
            ->map(fn (Member $member) => [
                $member->fullName(),
                $member->email ?? '',
                $member->phone ?? '',
                $member->gender?->value ? ucfirst($member->gender->value) : '',
                ucfirst($member->status->value),
                $member->joined_at?->format('Y-m-d') ?? '',
                $member->city ?? '',
            ]);
    }

    public function render()
    {
        return view('livewire.reports.membership.member-directory');
    }
}
