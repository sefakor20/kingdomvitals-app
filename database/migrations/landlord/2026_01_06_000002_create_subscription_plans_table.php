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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->integer('max_members')->nullable(); // null = unlimited
            $table->integer('max_branches')->nullable(); // null = unlimited
            $table->integer('storage_quota_gb')->default(5);
            $table->integer('sms_credits_monthly')->nullable();
            $table->json('enabled_modules')->nullable(); // array of module names
            $table->json('features')->nullable(); // additional feature flags
            $table->string('support_level', 20)->default('community'); // community, email, priority
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
