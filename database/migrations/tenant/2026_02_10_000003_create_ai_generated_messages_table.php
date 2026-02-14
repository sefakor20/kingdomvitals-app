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
        Schema::create('ai_generated_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_type'); // follow_up, reengagement, welcome
            $table->string('channel'); // sms, email, whatsapp
            $table->text('generated_content');
            $table->json('context_used')->nullable();
            $table->string('status')->default('pending'); // pending, approved, sent, rejected
            $table->string('ai_provider');
            $table->string('ai_model');
            $table->integer('tokens_used')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['visitor_id', 'message_type']);
            $table->index(['member_id', 'message_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generated_messages');
    }
};
