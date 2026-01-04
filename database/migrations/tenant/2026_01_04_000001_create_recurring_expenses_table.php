<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');

            // Template fields (same as Expense)
            $table->string('category', 50);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('payment_method', 20);
            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();

            // Recurrence configuration
            $table->string('frequency', 20);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable();

            // Tracking
            $table->date('next_generation_date')->nullable();
            $table->date('last_generated_date')->nullable();
            $table->unsignedInteger('total_generated_count')->default(0);

            // Status and metadata
            $table->string('status', 20)->default('active');
            $table->foreignUuid('created_by')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();

            $table->index('branch_id');
            $table->index('status');
            $table->index('next_generation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
