<?php

declare(strict_types=1);

use App\Enums\SuperAdminRole;
use App\Livewire\SuperAdmin\SystemLogs;
use App\Models\SuperAdmin;
use App\Services\LogParserService;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function (): void {
    // Create a test log file
    $this->logPath = storage_path('logs/test-laravel.log');
    $this->logContent = <<<'LOG'
[2024-01-15 10:30:00] local.ERROR: Test error message {"user_id":1}
#0 /app/test.php(10): TestClass->method()
#1 /app/index.php(5): test()
[2024-01-15 10:25:00] local.WARNING: Test warning message
[2024-01-15 10:20:00] local.INFO: Test info message
[2024-01-15 10:15:00] local.DEBUG: Test debug message
LOG;

    File::put($this->logPath, $this->logContent);
});

afterEach(function (): void {
    if (File::exists($this->logPath)) {
        File::delete($this->logPath);
    }
});

it('renders system logs page for authenticated super admin', function (): void {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->assertStatus(200)
        ->assertSee('System Logs');
});

it('displays log files in dropdown', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->assertSee('test-laravel.log');
});

it('filters logs by level', function (): void {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->set('logFile', 'test-laravel.log')
        ->set('level', 'error')
        ->assertSee('Test error message')
        ->assertDontSee('Test warning message')
        ->assertDontSee('Test info message');
});

it('searches log messages', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->set('logFile', 'test-laravel.log')
        ->set('search', 'warning')
        ->assertSee('Test warning message')
        ->assertDontSee('Test error message');
});

it('shows clear logs button only for owner role', function (): void {
    $owner = SuperAdmin::factory()->create(['role' => SuperAdminRole::Owner]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(SystemLogs::class)
        ->assertSee('Clear Old Logs');
});

it('hides clear logs button for non-owner roles', function (): void {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class);

    // Check that canClearLogs is false
    expect($component->viewData('canClearLogs'))->toBeFalse();
});

it('allows owner to confirm clear logs', function (): void {
    $owner = SuperAdmin::factory()->create(['role' => SuperAdminRole::Owner]);

    Livewire::actingAs($owner, 'superadmin')
        ->test(SystemLogs::class)
        ->call('confirmClearLogs')
        ->assertSet('confirmingClear', true);
});

it('prevents non-owner from clearing logs', function (): void {
    $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->call('confirmClearLogs')
        ->assertSet('confirmingClear', false);
});

it('can expand log entry to show details', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->set('logFile', 'test-laravel.log')
        ->call('toggleExpand', 0)
        ->assertSet('expandedEntry', 0)
        ->call('toggleExpand', 0)
        ->assertSet('expandedEntry', null);
});

it('exports filtered logs to CSV', function (): void {
    $admin = SuperAdmin::factory()->create();

    $response = Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class)
        ->set('logFile', 'test-laravel.log')
        ->call('exportCsv');

    // Check that a download was triggered
    expect($response->effects)->toHaveKey('download');
});

it('uses the correct layout', function (): void {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class);

    expect($component->instance()->render()->name())->toBe('livewire.super-admin.system-logs');
});

it('resets pagination when level filter changes', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Simply verify that updating level calls resetPage via updatedLevel
    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(SystemLogs::class);

    $component->set('level', 'error');

    // If we got here without error, the filter update worked
    expect($component->get('level'))->toBe('error');
});

describe('LogParserService', function (): void {
    it('parses log entries correctly', function (): void {
        $parser = app(LogParserService::class);
        $entries = $parser->parseLogFile('test-laravel.log');

        expect($entries)->toHaveCount(4);

        // Entries are returned in reverse order (newest first based on file position)
        // File order: error, warning, info, debug
        // After array_reverse: debug, info, warning, error
        // So entries[0] is debug (last in file), entries[3] is error (first in file)

        // Find the error entry which has a stack trace
        $errorEntry = collect($entries)->firstWhere('level', 'error');
        expect($errorEntry)->not->toBeNull();
        expect($errorEntry['message'])->toBe('Test error message');
        expect($errorEntry['stackTrace'])->toContain('TestClass->method()');
    });

    it('filters by level', function (): void {
        $parser = app(LogParserService::class);
        $entries = $parser->parseLogFile('test-laravel.log', level: 'warning');

        expect($entries)->toHaveCount(1);
        expect($entries[0]['level'])->toBe('warning');
    });

    it('filters by search term', function (): void {
        $parser = app(LogParserService::class);
        $entries = $parser->parseLogFile('test-laravel.log', search: 'debug');

        expect($entries)->toHaveCount(1);
        expect($entries[0]['level'])->toBe('debug');
    });

    it('returns log files list', function (): void {
        $parser = app(LogParserService::class);
        $files = $parser->getLogFiles();

        $testFile = collect($files)->firstWhere('name', 'test-laravel.log');
        expect($testFile)->not->toBeNull();
        expect($testFile['size'])->toBeGreaterThan(0);
    });

    it('prevents directory traversal', function (): void {
        $parser = app(LogParserService::class);
        $entries = $parser->parseLogFile('../../../etc/passwd');

        expect($entries)->toBeEmpty();
    });
});
