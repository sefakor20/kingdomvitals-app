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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('member_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone_number', 20);
            $table->text('message');
            $table->string('message_type', 30); // PHP Enum: SmsType (birthday, reminder, announcement, follow_up, custom)
            $table->string('status', 20)->default('pending'); // PHP Enum: SmsStatus (pending, sent, delivered, failed)
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignUuid('sent_by')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();

            $table->index('branch_id');
            $table->index('member_id');
            $table->index('phone_number');
            $table->index('status');
            $table->index('message_type');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
