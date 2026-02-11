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
        Schema::table('duty_roster_pool_member', function (Blueprint $table) {
            $table->string('experience_level')->default('intermediate')->after('is_active');
            $table->decimal('skill_score', 5, 2)->nullable()->after('experience_level');
            $table->decimal('reliability_score', 5, 2)->nullable()->after('skill_score');
            $table->json('preferred_service_ids')->nullable()->after('reliability_score');
            $table->unsignedTinyInteger('max_monthly_assignments')->nullable()->after('preferred_service_ids');
            $table->timestamp('scores_calculated_at')->nullable()->after('max_monthly_assignments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duty_roster_pool_member', function (Blueprint $table) {
            $table->dropColumn([
                'experience_level',
                'skill_score',
                'reliability_score',
                'preferred_service_ids',
                'max_monthly_assignments',
                'scores_calculated_at',
            ]);
        });
    }
};
