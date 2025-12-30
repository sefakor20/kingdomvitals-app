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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('category', 50); // PHP Enum: ExpenseCategory (utilities, salaries, maintenance, supplies, events, missions, other)
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->date('expense_date');
            $table->string('payment_method', 20); // PHP Enum: PaymentMethod
            $table->string('vendor_name')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('status', 20)->default('pending'); // PHP Enum: ExpenseStatus (pending, approved, rejected, paid)
            $table->foreignUuid('submitted_by')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignUuid('approved_by')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('category');
            $table->index('expense_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
