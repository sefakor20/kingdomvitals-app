<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds USD pricing columns to subscription_plans table.
     * Existing price_monthly and price_annual become GHS prices.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Add USD pricing columns after the existing GHS columns
            $table->decimal('price_monthly_usd', 10, 2)->nullable()->after('price_annual');
            $table->decimal('price_annual_usd', 10, 2)->nullable()->after('price_monthly_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['price_monthly_usd', 'price_annual_usd']);
        });
    }
};
