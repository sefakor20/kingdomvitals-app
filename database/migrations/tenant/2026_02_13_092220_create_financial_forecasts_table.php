<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_forecasts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('forecast_type'); // weekly, monthly, quarterly
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('predicted_total', 15, 2);
            $table->decimal('predicted_tithes', 15, 2)->default(0);
            $table->decimal('predicted_offerings', 15, 2)->default(0);
            $table->decimal('predicted_special', 15, 2)->default(0);
            $table->decimal('predicted_other', 15, 2)->default(0);
            $table->decimal('confidence_lower', 15, 2);
            $table->decimal('confidence_upper', 15, 2);
            $table->decimal('confidence_score', 5, 2);
            $table->json('factors')->nullable();
            $table->json('cohort_breakdown')->nullable();
            $table->decimal('actual_total', 15, 2)->nullable();
            $table->decimal('budget_target', 15, 2)->nullable();
            $table->decimal('gap_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'forecast_type', 'period_start']);
            $table->index(['branch_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_forecasts');
    }
};
