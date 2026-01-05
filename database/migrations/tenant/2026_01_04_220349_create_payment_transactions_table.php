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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('donation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('paystack_reference')->unique();
            $table->string('paystack_transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 20)->default('pending'); // pending, success, failed, abandoned
            $table->string('channel', 30)->nullable(); // card, mobile_money, bank, ussd
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('status');
            $table->index('paystack_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
