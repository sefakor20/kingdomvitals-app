<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use App\Models\Tenant;

class TenantProcessingResult
{
    public int $successCount = 0;

    public int $errorCount = 0;

    /**
     * @var array<string, \Throwable>
     */
    public array $errors = [];

    /**
     * @var array<string, mixed>
     */
    public array $metadata = [];

    public function recordSuccess(Tenant $tenant): void
    {
        $this->successCount++;
    }

    public function recordError(Tenant $tenant, \Throwable $e): void
    {
        $this->errorCount++;
        $this->errors[$tenant->id] = $e;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = ($this->metadata[$key] ?? 0) + $value;
    }

    public function hasErrors(): bool
    {
        return $this->errorCount > 0;
    }

    public function wasSuccessful(): bool
    {
        return $this->errorCount === 0;
    }
}
