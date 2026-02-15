<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use App\Models\Tenant;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ProcessesTenants
{
    /**
     * Process tenants with the given callback.
     *
     * @param  Closure(Tenant): mixed  $callback  The callback to execute for each tenant
     * @param  array{
     *   tenantId?: string|null,
     *   eagerLoad?: array<string>,
     *   chunkSize?: int,
     *   withProgress?: bool,
     *   continueOnError?: bool,
     *   filter?: Closure(Builder): void
     * }  $options
     */
    protected function processTenants(Closure $callback, array $options = []): TenantProcessingResult
    {
        $tenantId = $options['tenantId'] ?? $this->option('tenant');
        $eagerLoad = $options['eagerLoad'] ?? [];
        $chunkSize = $options['chunkSize'] ?? 0;
        $withProgress = $options['withProgress'] ?? false;
        $continueOnError = $options['continueOnError'] ?? true;
        $filter = $options['filter'] ?? null;

        $result = new TenantProcessingResult;

        $query = $this->buildTenantQuery($tenantId, $eagerLoad, $filter);
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warnNoTenants();

            return $result;
        }

        $progressBar = $withProgress
            ? $this->output->createProgressBar($tenants->count())
            : null;

        $progressBar?->start();

        $processor = function (Collection $batch) use ($callback, $result, $continueOnError, $progressBar): void {
            foreach ($batch as $tenant) {
                $this->processSingleTenant($tenant, $callback, $result, $continueOnError);
                $progressBar?->advance();
            }
        };

        if ($chunkSize > 0) {
            $tenants->chunk($chunkSize)->each($processor);
        } else {
            $processor($tenants);
        }

        $progressBar?->finish();

        if ($progressBar) {
            $this->newLine();
        }

        return $result;
    }

    /**
     * Process a single tenant within tenancy context.
     */
    protected function processSingleTenant(
        Tenant $tenant,
        Closure $callback,
        TenantProcessingResult $result,
        bool $continueOnError
    ): void {
        $this->outputTenantProgress($tenant);

        try {
            tenancy()->initialize($tenant);
            $callback($tenant);
            $result->recordSuccess($tenant);
        } catch (\Throwable $e) {
            $result->recordError($tenant, $e);
            $this->outputTenantError($tenant, $e);

            if (! $continueOnError) {
                throw $e;
            }
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Build the tenant query with optional filters and eager loading.
     */
    protected function buildTenantQuery(
        ?string $tenantId,
        array $eagerLoad,
        ?Closure $filter
    ): Builder {
        $query = Tenant::query();

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        if ($eagerLoad !== []) {
            $query->with($eagerLoad);
        }

        if ($filter instanceof \Closure) {
            $filter($query);
        }

        return $query;
    }

    /**
     * Output tenant processing progress.
     */
    protected function outputTenantProgress(Tenant $tenant): void
    {
        $this->line("Processing tenant: {$tenant->name} ({$tenant->id})");
    }

    /**
     * Output tenant processing error.
     */
    protected function outputTenantError(Tenant $tenant, \Throwable $e): void
    {
        $this->error("  Error processing tenant {$tenant->id}: {$e->getMessage()}");
    }

    /**
     * Warn when no tenants are found.
     */
    protected function warnNoTenants(): void
    {
        $this->warn('No tenants found to process.');
    }

    /**
     * Output the final processing summary.
     */
    protected function outputProcessingSummary(TenantProcessingResult $result): void
    {
        $this->newLine();
        $this->info("Done! Processed {$result->successCount} tenant(s) successfully.");

        if ($result->errorCount > 0) {
            $this->warn("  {$result->errorCount} tenant(s) had errors.");
        }
    }
}
