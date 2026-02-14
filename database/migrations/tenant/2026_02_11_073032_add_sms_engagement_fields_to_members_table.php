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
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('sms_engagement_score', 5, 2)->nullable()->after('sms_opt_out');
            $table->string('sms_engagement_level')->nullable()->after('sms_engagement_score');
            $table->unsignedTinyInteger('sms_optimal_send_hour')->nullable()->after('sms_engagement_level');
            $table->unsignedTinyInteger('sms_optimal_send_day')->nullable()->after('sms_optimal_send_hour');
            $table->decimal('sms_response_rate', 5, 2)->nullable()->after('sms_optimal_send_day');
            $table->timestamp('sms_last_engaged_at')->nullable()->after('sms_response_rate');
            $table->unsignedInteger('sms_total_received')->default(0)->after('sms_last_engaged_at');
            $table->unsignedInteger('sms_total_delivered')->default(0)->after('sms_total_received');
            $table->timestamp('sms_engagement_calculated_at')->nullable()->after('sms_total_delivered');

            $table->index('sms_engagement_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['sms_engagement_level']);
            $table->dropColumn([
                'sms_engagement_score',
                'sms_engagement_level',
                'sms_optimal_send_hour',
                'sms_optimal_send_day',
                'sms_response_rate',
                'sms_last_engaged_at',
                'sms_total_received',
                'sms_total_delivered',
                'sms_engagement_calculated_at',
            ]);
        });
    }
};
