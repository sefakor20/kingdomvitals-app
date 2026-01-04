<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RecurringExpenseStatus;
use App\Models\Tenant;
use App\Models\Tenant\RecurringExpense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringExpensesCommand extends Command
{
    protected $signature = 'expenses:generate-recurring {--dry-run : Show what would be generated without actually creating expenses}';

    protected $description = 'Generate expenses from active recurring expense templates';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No expenses will actually be created');
        }

        $this->info('Starting recurring expense generation...');

        $totalGenerated = 0;
        $totalSkipped = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($dryRun, &$totalGenerated, &$totalSkipped) {
            tenancy()->initialize($tenant);

            $this->line("Processing tenant: {$tenant->id}");

            $recurringExpenses = RecurringExpense::where('status', RecurringExpenseStatus::Active)
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now()->toDateString());
                })
                ->where('next_generation_date', '<=', now()->toDateString())
                ->with('branch')
                ->get();

            if ($recurringExpenses->isEmpty()) {
                $this->line('  No recurring expenses due for generation');
                tenancy()->end();

                return;
            }

            $this->info("  Found {$recurringExpenses->count()} recurring expense(s) due");

            foreach ($recurringExpenses as $recurringExpense) {
                $branch = $recurringExpense->branch;

                if ($dryRun) {
                    $this->line("  - Would generate: {$recurringExpense->description} (GHS {$recurringExpense->amount}) for {$branch->name}");
                    $totalGenerated++;

                    continue;
                }

                try {
                    $expense = $recurringExpense->generateExpense();

                    if ($expense) {
                        $this->line("  - Generated: {$expense->description} (GHS {$expense->amount}) for {$branch->name}");
                        $totalGenerated++;
                    } else {
                        $this->line("  - Skipped: {$recurringExpense->description} (not due or already generated)");
                        $totalSkipped++;
                    }
                } catch (\Exception $e) {
                    $this->error("  - Failed: {$recurringExpense->description} - {$e->getMessage()}");
                    Log::error('Recurring expense generation failed', [
                        'recurring_expense_id' => $recurringExpense->id,
                        'branch_id' => $branch->id,
                        'error' => $e->getMessage(),
                    ]);
                    $totalSkipped++;
                }
            }

            tenancy()->end();
        });

        $this->newLine();
        $this->info("Done! Generated {$totalGenerated} expense(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }
}
