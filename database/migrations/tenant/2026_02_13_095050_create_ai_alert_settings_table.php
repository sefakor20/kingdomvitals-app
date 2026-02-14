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
        Schema::create('ai_alert_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type');
            $table->boolean('is_enabled')->default(true);
            $table->integer('threshold_value')->nullable();
            $table->json('notification_channels')->nullable();
            $table->json('recipient_roles')->nullable();
            $table->integer('cooldown_hours')->default(24);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_alert_settings');
    }
};
