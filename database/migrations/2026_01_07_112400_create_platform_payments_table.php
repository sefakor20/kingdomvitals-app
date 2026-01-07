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
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('platform_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->string('payment_reference')->unique();
            $table->string('paystack_reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('payment_method');
            $table->string('status');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_payments');
    }
};
