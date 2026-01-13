<?php

declare(strict_types=1);

namespace App\Livewire\Reports\Membership;

use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class MemberDemographics extends Component
{
    use HasReportExport;

    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', $branch);
        $this->branch = $branch;
    }

    #[Computed]
    public function totalMembers(): int
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->count();
    }

    #[Computed]
    public function genderDistribution(): array
    {
        $data = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(fn ($item): array => [$item->gender->name => $item->count])
            ->toArray();

        $unspecified = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNull('gender')
            ->count();

        if ($unspecified > 0) {
            $data['Unspecified'] = $unspecified;
        }

        return $data;
    }

    #[Computed]
    public function genderChartData(): array
    {
        $colors = [
            'Male' => 'rgb(59, 130, 246)',
            'Female' => 'rgb(236, 72, 153)',
            'Other' => 'rgb(168, 85, 247)',
            'Unspecified' => 'rgb(156, 163, 175)',
        ];

        return [
            'labels' => array_keys($this->genderDistribution),
            'data' => array_values($this->genderDistribution),
            'colors' => array_map(fn (int|string $key): string => $colors[$key] ?? 'rgb(156, 163, 175)', array_keys($this->genderDistribution)),
        ];
    }

    #[Computed]
    public function ageDistribution(): array
    {
        $members = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth']);

        $ageGroups = [
            '0-12' => 0,
            '13-17' => 0,
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56-65' => 0,
            '65+' => 0,
        ];

        foreach ($members as $member) {
            $age = $member->date_of_birth->age;
            match (true) {
                $age <= 12 => $ageGroups['0-12']++,
                $age <= 17 => $ageGroups['13-17']++,
                $age <= 25 => $ageGroups['18-25']++,
                $age <= 35 => $ageGroups['26-35']++,
                $age <= 45 => $ageGroups['36-45']++,
                $age <= 55 => $ageGroups['46-55']++,
                $age <= 65 => $ageGroups['56-65']++,
                default => $ageGroups['65+']++,
            };
        }

        $noAge = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNull('date_of_birth')
            ->count();

        if ($noAge > 0) {
            $ageGroups['Unknown'] = $noAge;
        }

        return $ageGroups;
    }

    #[Computed]
    public function ageChartData(): array
    {
        return [
            'labels' => array_keys($this->ageDistribution),
            'data' => array_values($this->ageDistribution),
        ];
    }

    #[Computed]
    public function maritalStatusDistribution(): array
    {
        $data = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('marital_status')
            ->selectRaw('marital_status, COUNT(*) as count')
            ->groupBy('marital_status')
            ->get()
            ->mapWithKeys(fn ($item): array => [$item->marital_status->name => $item->count])
            ->toArray();

        $unspecified = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNull('marital_status')
            ->count();

        if ($unspecified > 0) {
            $data['Unspecified'] = $unspecified;
        }

        return $data;
    }

    #[Computed]
    public function maritalChartData(): array
    {
        $colors = [
            'Single' => 'rgb(34, 197, 94)',
            'Married' => 'rgb(59, 130, 246)',
            'Divorced' => 'rgb(249, 115, 22)',
            'Widowed' => 'rgb(107, 114, 128)',
            'Separated' => 'rgb(236, 72, 153)',
            'Unspecified' => 'rgb(156, 163, 175)',
        ];

        return [
            'labels' => array_keys($this->maritalStatusDistribution),
            'data' => array_values($this->maritalStatusDistribution),
            'colors' => array_map(fn (int|string $key): string => $colors[$key] ?? 'rgb(156, 163, 175)', array_keys($this->maritalStatusDistribution)),
        ];
    }

    #[Computed]
    public function averageAge(): ?float
    {
        $avgDays = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->whereNotNull('date_of_birth')
            ->selectRaw('AVG(DATEDIFF(NOW(), date_of_birth)) as avg_days')
            ->value('avg_days');

        return $avgDays ? round($avgDays / 365.25, 1) : null;
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Category', 'Value', 'Count', 'Percentage'];
        $filename = $this->generateFilename('demographics', 'csv');

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->authorize('exportReports', $this->branch);

        $data = $this->getExportData();
        $headers = ['Category', 'Value', 'Count', 'Percentage'];
        $filename = $this->generateFilename('demographics', 'xlsx');

        return $this->exportToExcel($data, $headers, $filename);
    }

    protected function getExportData(): Collection
    {
        $total = $this->totalMembers;
        $data = collect();

        // Gender data
        foreach ($this->genderDistribution as $gender => $count) {
            $data->push([
                'Gender',
                $gender,
                $count,
                $total > 0 ? round(($count / $total) * 100, 1).'%' : '0%',
            ]);
        }

        // Age data
        foreach ($this->ageDistribution as $ageGroup => $count) {
            $data->push([
                'Age Group',
                $ageGroup,
                $count,
                $total > 0 ? round(($count / $total) * 100, 1).'%' : '0%',
            ]);
        }

        // Marital status data
        foreach ($this->maritalStatusDistribution as $status => $count) {
            $data->push([
                'Marital Status',
                $status,
                $count,
                $total > 0 ? round(($count / $total) * 100, 1).'%' : '0%',
            ]);
        }

        return $data;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.reports.membership.member-demographics');
    }
}
