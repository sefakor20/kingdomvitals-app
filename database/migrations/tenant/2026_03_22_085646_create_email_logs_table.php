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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('member_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email_address');
            $table->string('subject');
            $table->text('body');
            $table->string('message_type', 30); // PHP Enum: EmailType (birthday, reminder, announcement, welcome, follow_up, newsletter, event_reminder, custom)
            $table->string('status', 20)->default('pending'); // PHP Enum: EmailStatus (pending, sent, delivered, bounced, failed)
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('branch_id');
            $table->index('member_id');
            $table->index('email_address');
            $table->index('status');
            $table->index('message_type');
            $table->index('sent_at');
            $table->index('opened_at');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
