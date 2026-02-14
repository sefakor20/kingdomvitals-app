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
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('forecast_next_attendance', 8, 2)->nullable()->after('is_active');
            $table->decimal('forecast_confidence', 5, 2)->nullable()->after('forecast_next_attendance');
            $table->json('forecast_factors')->nullable()->after('forecast_confidence');
            $table->timestamp('forecast_calculated_at')->nullable()->after('forecast_factors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'forecast_next_attendance',
                'forecast_confidence',
                'forecast_factors',
                'forecast_calculated_at',
            ]);
        });
    }
};
