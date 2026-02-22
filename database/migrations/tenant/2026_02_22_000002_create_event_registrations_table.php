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
        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('member_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('visitor_id')->nullable()->constrained()->onDelete('cascade');

            // Guest info (for non-member/visitor registrations)
            $table->string('guest_name', 150)->nullable();
            $table->string('guest_email', 150)->nullable();
            $table->string('guest_phone', 30)->nullable();

            // Status
            $table->string('status', 20)->default('registered');
            $table->dateTime('registered_at');
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->onDelete('set null');

            // Ticket
            $table->string('ticket_number', 50)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('price_paid', 8, 2)->nullable();
            $table->boolean('requires_payment')->default(false);

            // Payment reference
            $table->foreignUuid('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_reference', 100)->nullable();

            // Attendance
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('check_in_method', 20)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('event_id');
            $table->index('branch_id');
            $table->index('member_id');
            $table->index('visitor_id');
            $table->index('status');
            $table->index('registered_at');
            $table->index('ticket_number');

            // Unique constraints - prevent duplicate registrations
            $table->unique(['event_id', 'member_id'], 'unique_member_event_registration');
            $table->unique(['event_id', 'visitor_id'], 'unique_visitor_event_registration');
            $table->unique(['event_id', 'guest_email'], 'unique_guest_event_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
