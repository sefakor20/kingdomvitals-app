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
        Schema::create('platform_payment_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('platform_invoice_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('channel');
            $table->timestamp('sent_at');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();

            $table->timestamps();

            $table->index(['platform_invoice_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_payment_reminders');
    }
};
