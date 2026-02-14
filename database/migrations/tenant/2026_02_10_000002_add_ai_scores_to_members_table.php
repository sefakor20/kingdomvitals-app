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
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('churn_risk_score', 5, 2)->nullable()->after('notes');
            $table->json('churn_risk_factors')->nullable()->after('churn_risk_score');
            $table->timestamp('churn_risk_calculated_at')->nullable()->after('churn_risk_factors');
            $table->decimal('attendance_anomaly_score', 5, 2)->nullable()->after('churn_risk_calculated_at');
            $table->timestamp('attendance_anomaly_detected_at')->nullable()->after('attendance_anomaly_score');

            $table->index('churn_risk_score');
            $table->index('attendance_anomaly_detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['churn_risk_score']);
            $table->dropIndex(['attendance_anomaly_detected_at']);
            $table->dropColumn([
                'churn_risk_score',
                'churn_risk_factors',
                'churn_risk_calculated_at',
                'attendance_anomaly_score',
                'attendance_anomaly_detected_at',
            ]);
        });
    }
};
