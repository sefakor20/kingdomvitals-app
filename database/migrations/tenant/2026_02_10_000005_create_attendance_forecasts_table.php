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
        Schema::create('attendance_forecasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->integer('predicted_attendance');
            $table->integer('predicted_members');
            $table->integer('predicted_visitors');
            $table->decimal('confidence_score', 5, 2);
            $table->json('factors')->nullable();
            $table->integer('actual_attendance')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'forecast_date']);
            $table->index(['branch_id', 'forecast_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_forecasts');
    }
};
