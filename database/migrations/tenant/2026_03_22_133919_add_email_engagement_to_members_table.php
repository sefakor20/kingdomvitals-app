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
        Schema::table('members', function (Blueprint $table): void {
            $table->decimal('email_engagement_score', 5, 2)->nullable()->after('sms_engagement_calculated_at');
            $table->string('email_engagement_level', 20)->nullable()->after('email_engagement_score');
            $table->decimal('email_open_rate', 5, 2)->nullable()->after('email_engagement_level');
            $table->decimal('email_click_rate', 5, 2)->nullable()->after('email_open_rate');
            $table->tinyInteger('email_optimal_send_hour')->unsigned()->nullable()->after('email_click_rate');
            $table->tinyInteger('email_optimal_send_day')->unsigned()->nullable()->after('email_optimal_send_hour');
            $table->timestamp('email_last_engaged_at')->nullable()->after('email_optimal_send_day');
            $table->integer('email_total_sent')->unsigned()->default(0)->after('email_last_engaged_at');
            $table->integer('email_total_opened')->unsigned()->default(0)->after('email_total_sent');
            $table->integer('email_total_clicked')->unsigned()->default(0)->after('email_total_opened');
            $table->timestamp('email_engagement_calculated_at')->nullable()->after('email_total_clicked');

            // Add index for engagement level queries
            $table->index('email_engagement_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex(['email_engagement_level']);
            $table->dropColumn([
                'email_engagement_score',
                'email_engagement_level',
                'email_open_rate',
                'email_click_rate',
                'email_optimal_send_hour',
                'email_optimal_send_day',
                'email_last_engaged_at',
                'email_total_sent',
                'email_total_opened',
                'email_total_clicked',
                'email_engagement_calculated_at',
            ]);
        });
    }
};
