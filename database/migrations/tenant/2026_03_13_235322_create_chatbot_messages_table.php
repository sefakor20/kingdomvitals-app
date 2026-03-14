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
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content');
            $table->string('intent', 50)->nullable(); // giving_history, events, prayer, cluster_info, help, unknown
            $table->json('extracted_entities')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('provider', 50)->default('heuristic');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at'], 'cm_conversation_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
