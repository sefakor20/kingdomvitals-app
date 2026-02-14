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
        Schema::create('prayer_summaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('period_type'); // weekly, monthly
            $table->date('period_start');
            $table->date('period_end');
            $table->json('category_breakdown'); // counts per category
            $table->json('urgency_breakdown'); // counts per urgency level
            $table->text('summary_text'); // AI-generated narrative
            $table->json('key_themes'); // extracted themes
            $table->json('pastoral_recommendations'); // action items
            $table->unsignedInteger('total_requests');
            $table->unsignedInteger('answered_requests');
            $table->unsignedInteger('critical_requests');
            $table->timestamps();

            $table->unique(['branch_id', 'period_type', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_summaries');
    }
};
