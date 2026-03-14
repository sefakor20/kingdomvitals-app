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
        Schema::create('event_attendance_predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('attendance_probability', 5, 2); // 0.00-100.00
            $table->string('prediction_tier', 20); // high, medium, low
            $table->json('factors')->nullable();
            $table->boolean('invitation_sent')->default(false);
            $table->timestamp('invitation_sent_at')->nullable();
            $table->string('invitation_channel', 20)->nullable(); // sms, email
            $table->string('provider', 50)->default('heuristic');
            $table->timestamps();

            $table->unique(['event_id', 'member_id'], 'eap_event_member_unique');
            $table->index(['branch_id', 'attendance_probability'], 'eap_branch_prob_index');
            $table->index(['event_id', 'prediction_tier'], 'eap_event_tier_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendance_predictions');
    }
};
