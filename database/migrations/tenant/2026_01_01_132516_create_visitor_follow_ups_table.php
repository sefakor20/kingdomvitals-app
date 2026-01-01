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
        Schema::create('visitor_follow_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visitor_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('performed_by')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type', 20);
            $table->string('outcome', 20);
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index(['visitor_id', 'scheduled_at']);
            $table->index(['is_scheduled', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_follow_ups');
    }
};
