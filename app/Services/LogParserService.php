<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use SplFileObject;

class LogParserService
{
    private const LOG_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s+(.*)$/';

    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

    /**
     * Get list of available log files.
     *
     * @return array<int, array{name: string, path: string, size: int, modified: Carbon}>
     */
    public function getLogFiles(): array
    {
        $logPath = storage_path('logs');

        if (! File::isDirectory($logPath)) {
            return [];
        }

        $files = File::glob($logPath.'/*.log');

        return collect($files)
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'path' => $path,
                'size' => File::size($path),
                'modified' => Carbon::createFromTimestamp(File::lastModified($path)),
            ])
            ->sortByDesc('modified')
            ->values()
            ->all();
    }

    /**
     * Parse log entries from a specific file with optional filters.
     *
     * @return array<int, array{timestamp: Carbon, level: string, environment: string, message: string, context: array|null, stackTrace: string|null}>
     */
    public function parseLogFile(
        string $filename,
        ?string $level = null,
        ?string $search = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $path = $this->resolveLogPath($filename);

        if (! $path || ! File::exists($path)) {
            return [];
        }

        return $this->parseEntries($path, $level, $search, $startDate, $endDate);
    }

    /**
     * Get paginated entries with offset and limit.
     *
     * @return array<int, array{timestamp: Carbon, level: string, environment: string, message: string, context: array|null, stackTrace: string|null}>
     */
    public function getPaginatedEntries(
        string $filename,
        int $offset,
        int $limit,
        ?string $level = null,
        ?string $search = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $entries = $this->parseLogFile($filename, $level, $search, $startDate, $endDate);

        return array_slice($entries, $offset, $limit);
    }

    /**
     * Get total entry count for pagination.
     */
    public function countEntries(
        string $filename,
        ?string $level = null,
        ?string $search = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): int {
        return count($this->parseLogFile($filename, $level, $search, $startDate, $endDate));
    }

    /**
     * Delete a specific log file.
     */
    public function deleteLogFile(string $filename): bool
    {
        $path = $this->resolveLogPath($filename);

        if (! $path || ! File::exists($path)) {
            return false;
        }

        // Never delete the main laravel.log while it's being written to
        if ($filename === 'laravel.log') {
            // Truncate instead of delete
            File::put($path, '');

            return true;
        }

        return File::delete($path);
    }

    /**
     * Clear all log files older than given days.
     *
     * @return int Number of files deleted
     */
    public function clearOldLogs(int $daysOld = 30): int
    {
        $logPath = storage_path('logs');
        $cutoff = Carbon::now()->subDays($daysOld);
        $deleted = 0;

        $files = File::glob($logPath.'/*.log');

        foreach ($files as $file) {
            $filename = basename($file);

            // Skip the main laravel.log
            if ($filename === 'laravel.log') {
                continue;
            }

            $modified = Carbon::createFromTimestamp(File::lastModified($file));

            if ($modified->lt($cutoff)) {
                if (File::delete($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Check if a file is too large to parse safely.
     */
    public function isFileTooLarge(string $filename): bool
    {
        $path = $this->resolveLogPath($filename);

        if (! $path || ! File::exists($path)) {
            return false;
        }

        return File::size($path) > self::MAX_FILE_SIZE;
    }

    /**
     * Get available log levels.
     *
     * @return array<string>
     */
    public function getLogLevels(): array
    {
        return [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];
    }

    /**
     * Resolve the full path for a log filename.
     */
    private function resolveLogPath(string $filename): ?string
    {
        // Prevent directory traversal
        $filename = basename($filename);

        if (! str_ends_with($filename, '.log')) {
            return null;
        }

        $path = storage_path('logs/'.$filename);

        // Ensure path is within logs directory
        if (! str_starts_with(realpath($path) ?: '', realpath(storage_path('logs')) ?: '')) {
            return null;
        }

        return $path;
    }

    /**
     * Parse entries from a log file.
     *
     * @return array<int, array{timestamp: Carbon, level: string, environment: string, message: string, context: array|null, stackTrace: string|null}>
     */
    private function parseEntries(
        string $path,
        ?string $level,
        ?string $search,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): array {
        $entries = [];
        $currentEntry = null;
        $stackTraceLines = [];

        try {
            $file = new SplFileObject($path, 'r');
            $file->setFlags(SplFileObject::DROP_NEW_LINE);

            while (! $file->eof()) {
                $line = $file->fgets();

                if ($line === false) {
                    continue;
                }

                // Check if this is a new log entry
                if (preg_match(self::LOG_PATTERN, $line, $matches)) {
                    // Save the previous entry if exists
                    if ($currentEntry !== null) {
                        $currentEntry['stackTrace'] = ! empty($stackTraceLines)
                            ? implode("\n", $stackTraceLines)
                            : null;

                        if ($this->matchesFilters($currentEntry, $level, $search, $startDate, $endDate)) {
                            $entries[] = $currentEntry;
                        }
                    }

                    // Parse the message and extract context if JSON is present
                    $message = $matches[4];
                    $context = null;

                    // Try to extract JSON context from the end of the message
                    if (preg_match('/^(.*?)(\s*\{.*\})\s*$/', $message, $contextMatches)) {
                        $message = trim($contextMatches[1]);
                        $jsonStr = $contextMatches[2];

                        $decoded = json_decode($jsonStr, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $context = $decoded;
                        } else {
                            // JSON decode failed, keep original message
                            $message = $matches[4];
                        }
                    }

                    $currentEntry = [
                        'timestamp' => Carbon::parse($matches[1]),
                        'level' => strtolower($matches[3]),
                        'environment' => $matches[2],
                        'message' => $message,
                        'context' => $context,
                        'stackTrace' => null,
                    ];

                    $stackTraceLines = [];
                } elseif ($currentEntry !== null && trim($line) !== '') {
                    // This is part of a stack trace or multiline message
                    $stackTraceLines[] = $line;
                }
            }

            // Don't forget the last entry
            if ($currentEntry !== null) {
                $currentEntry['stackTrace'] = ! empty($stackTraceLines)
                    ? implode("\n", $stackTraceLines)
                    : null;

                if ($this->matchesFilters($currentEntry, $level, $search, $startDate, $endDate)) {
                    $entries[] = $currentEntry;
                }
            }
        } catch (\Exception) {
            // Return empty array on error
            return [];
        }

        // Return in reverse order (newest first)
        return array_reverse($entries);
    }

    /**
     * Check if an entry matches the given filters.
     *
     * @param  array{timestamp: Carbon, level: string, environment: string, message: string, context: array|null, stackTrace: string|null}  $entry
     */
    private function matchesFilters(
        array $entry,
        ?string $level,
        ?string $search,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): bool {
        // Level filter
        if ($level && $entry['level'] !== strtolower($level)) {
            return false;
        }

        // Date range filter
        if ($startDate && $entry['timestamp']->lt($startDate->startOfDay())) {
            return false;
        }

        if ($endDate && $entry['timestamp']->gt($endDate->endOfDay())) {
            return false;
        }

        // Search filter
        if ($search) {
            $searchLower = strtolower($search);
            $messageMatch = str_contains(strtolower($entry['message']), $searchLower);
            $stackMatch = $entry['stackTrace'] && str_contains(strtolower($entry['stackTrace']), $searchLower);

            if (! $messageMatch && ! $stackMatch) {
                return false;
            }
        }

        return true;
    }
}
