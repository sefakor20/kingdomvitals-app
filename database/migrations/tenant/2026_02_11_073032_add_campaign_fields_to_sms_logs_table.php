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
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable()->after('id');
            $table->string('variant')->nullable()->after('campaign_id');
            $table->string('send_time_slot')->nullable()->after('variant');
            $table->boolean('was_optimal_time')->default(false)->after('send_time_slot');

            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropColumn([
                'campaign_id',
                'variant',
                'send_time_slot',
                'was_optimal_time',
            ]);
        });
    }
};
