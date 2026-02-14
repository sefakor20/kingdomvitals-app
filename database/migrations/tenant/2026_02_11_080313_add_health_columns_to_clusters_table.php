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
        Schema::table('clusters', function (Blueprint $table) {
            $table->decimal('health_score', 5, 2)->nullable()->after('is_active');
            $table->string('health_level', 20)->nullable()->after('health_score');
            $table->json('health_factors')->nullable()->after('health_level');
            $table->timestamp('health_calculated_at')->nullable()->after('health_factors');

            $table->index('health_score');
            $table->index('health_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clusters', function (Blueprint $table) {
            $table->dropIndex(['health_score']);
            $table->dropIndex(['health_level']);

            $table->dropColumn([
                'health_score',
                'health_level',
                'health_factors',
                'health_calculated_at',
            ]);
        });
    }
};
