<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Livewire\Concerns\HasReportExport;
use App\Models\SuperAdminActivityLog;
use App\Services\LogParserService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogs extends Component
{
    use HasReportExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $logFile = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public int $perPage = 50;

    public ?int $expandedEntry = null;

    public bool $confirmingClear = false;

    public int $clearDaysOld = 30;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedLogFile(): void
    {
        $this->resetPage();
        $this->expandedEntry = null;
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function toggleExpand(int $index): void
    {
        $this->expandedEntry = $this->expandedEntry === $index ? null : $index;
    }

    public function exportCsv(): StreamedResponse
    {
        $logParser = app(LogParserService::class);
        $selectedFile = $this->getSelectedFile($logParser);

        $entries = $logParser->parseLogFile(
            $selectedFile,
            $this->level ?: null,
            $this->search ?: null,
            $this->startDate ? Carbon::parse($this->startDate) : null,
            $this->endDate ? Carbon::parse($this->endDate) : null
        );

        $data = collect($entries)->map(fn (array $entry): array => [
            'timestamp' => $entry['timestamp']->format('Y-m-d H:i:s'),
            'level' => strtoupper($entry['level']),
            'environment' => $entry['environment'],
            'message' => $entry['message'],
            'context' => $entry['context'] ? json_encode($entry['context']) : '',
            'stack_trace' => $entry['stackTrace'] ?? '',
        ]);

        $headers = [
            'Timestamp',
            'Level',
            'Environment',
            'Message',
            'Context',
            'Stack Trace',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_system_logs',
            description: 'Exported system logs to CSV',
            metadata: [
                'record_count' => count($entries),
                'log_file' => $selectedFile,
                'filters' => [
                    'level' => $this->level,
                    'search' => $this->search,
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ],
            ],
        );

        $filename = 'system-logs-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

    public function confirmClearLogs(): void
    {
        if (! $this->canClearLogs()) {
            return;
        }

        $this->confirmingClear = true;
    }

    public function clearOldLogs(): void
    {
        if (! $this->canClearLogs()) {
            return;
        }

        $logParser = app(LogParserService::class);
        $deletedCount = $logParser->clearOldLogs($this->clearDaysOld);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'clear_system_logs',
            description: "Cleared {$deletedCount} log files older than {$this->clearDaysOld} days",
            metadata: [
                'deleted_count' => $deletedCount,
                'days_old' => $this->clearDaysOld,
            ],
        );

        $this->confirmingClear = false;
        $this->dispatch('notify', message: __(':count log files cleared.', ['count' => $deletedCount]));
    }

    public function deleteLogFile(string $filename): void
    {
        if (! $this->canClearLogs()) {
            return;
        }

        $logParser = app(LogParserService::class);

        if ($logParser->deleteLogFile($filename)) {
            SuperAdminActivityLog::log(
                superAdmin: Auth::guard('superadmin')->user(),
                action: 'delete_log_file',
                description: "Deleted log file: {$filename}",
                metadata: ['filename' => $filename],
            );

            $this->logFile = '';
            $this->dispatch('notify', message: __('Log file deleted.'));
        }
    }

    public function render(): View
    {
        $logParser = app(LogParserService::class);
        $logFiles = $logParser->getLogFiles();

        $selectedFile = $this->getSelectedFile($logParser);

        // Check if file is too large
        $fileTooLarge = $selectedFile && $logParser->isFileTooLarge($selectedFile);

        $entries = [];
        $total = 0;

        if ($selectedFile && ! $fileTooLarge) {
            $page = $this->getPage();
            $offset = ($page - 1) * $this->perPage;

            $entries = $logParser->getPaginatedEntries(
                $selectedFile,
                $offset,
                $this->perPage,
                $this->level ?: null,
                $this->search ?: null,
                $this->startDate ? Carbon::parse($this->startDate) : null,
                $this->endDate ? Carbon::parse($this->endDate) : null
            );

            $total = $logParser->countEntries(
                $selectedFile,
                $this->level ?: null,
                $this->search ?: null,
                $this->startDate ? Carbon::parse($this->startDate) : null,
                $this->endDate ? Carbon::parse($this->endDate) : null
            );
        }

        $paginator = new LengthAwarePaginator(
            $entries,
            $total,
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url()]
        );

        return view('livewire.super-admin.system-logs', [
            'logs' => $paginator,
            'logFiles' => $logFiles,
            'levels' => $logParser->getLogLevels(),
            'canClearLogs' => $this->canClearLogs(),
            'fileTooLarge' => $fileTooLarge,
            'selectedFile' => $selectedFile,
        ])->layout('components.layouts.superadmin.app');
    }

    private function getSelectedFile(LogParserService $logParser): string
    {
        if ($this->logFile) {
            return $this->logFile;
        }

        $files = $logParser->getLogFiles();

        return $files[0]['name'] ?? '';
    }

    private function canClearLogs(): bool
    {
        $user = Auth::guard('superadmin')->user();

        return $user && $user->role === SuperAdminRole::Owner;
    }
}
