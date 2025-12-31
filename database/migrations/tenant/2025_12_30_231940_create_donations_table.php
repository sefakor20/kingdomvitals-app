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
        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('member_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('service_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('donation_type', 30); // PHP Enum: DonationType (tithe, offering, building_fund, missions, special)
            $table->string('payment_method', 20); // PHP Enum: PaymentMethod (cash, check, card, mobile_money, bank_transfer)
            $table->date('donation_date');
            $table->string('reference_number')->nullable();
            $table->string('donor_name')->nullable(); // For anonymous or non-member donations
            $table->text('notes')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->foreignUuid('recorded_by')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();

            $table->index('branch_id');
            $table->index('member_id');
            $table->index('donation_date');
            $table->index('donation_type');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
