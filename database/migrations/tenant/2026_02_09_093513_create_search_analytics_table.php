<?php

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
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->string('query', 255);
            $table->string('query_normalized', 255)->index();
            $table->boolean('searched_all_branches')->default(false);
            $table->unsignedInteger('results_count')->default(0);
            $table->json('results_by_type')->nullable();
            $table->string('selected_type', 50)->nullable();
            $table->uuid('selected_id')->nullable();
            $table->timestamps();

            $table->index(['query_normalized', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_analytics');
    }
};
