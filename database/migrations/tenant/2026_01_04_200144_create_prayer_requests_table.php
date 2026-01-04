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
        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignUuid('cluster_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 150);
            $table->text('description');
            $table->string('category', 50);
            $table->string('status', 20)->default('open');
            $table->string('privacy', 20)->default('public');

            $table->timestamp('submitted_at');
            $table->timestamp('answered_at')->nullable();
            $table->text('answer_details')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['member_id', 'created_at']);
            $table->index(['cluster_id', 'status']);
            $table->index(['privacy', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_requests');
    }
};
