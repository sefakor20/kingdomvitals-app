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
        Schema::create('pledge_predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pledge_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('fulfillment_probability', 5, 2); // 0.00-100.00
            $table->string('risk_level', 20); // high, medium, low
            $table->timestamp('recommended_nudge_at')->nullable();
            $table->json('factors')->nullable();
            $table->string('provider', 50)->default('heuristic');
            $table->timestamps();

            $table->unique(['pledge_id', 'member_id'], 'pp_pledge_member_unique');
            $table->index(['branch_id', 'risk_level'], 'pp_branch_risk_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pledge_predictions');
    }
};
