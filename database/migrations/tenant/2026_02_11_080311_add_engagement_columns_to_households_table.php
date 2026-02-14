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
        Schema::table('households', function (Blueprint $table) {
            $table->decimal('engagement_score', 5, 2)->nullable()->after('address');
            $table->string('engagement_level', 20)->nullable()->after('engagement_score');
            $table->decimal('attendance_score', 5, 2)->nullable()->after('engagement_level');
            $table->decimal('giving_score', 5, 2)->nullable()->after('attendance_score');
            $table->decimal('member_engagement_variance', 5, 2)->nullable()->after('giving_score');
            $table->json('engagement_factors')->nullable()->after('member_engagement_variance');
            $table->timestamp('engagement_calculated_at')->nullable()->after('engagement_factors');

            $table->index('engagement_score');
            $table->index('engagement_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropIndex(['engagement_score']);
            $table->dropIndex(['engagement_level']);

            $table->dropColumn([
                'engagement_score',
                'engagement_level',
                'attendance_score',
                'giving_score',
                'member_engagement_variance',
                'engagement_factors',
                'engagement_calculated_at',
            ]);
        });
    }
};
