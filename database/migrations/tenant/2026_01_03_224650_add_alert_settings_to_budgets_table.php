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
        Schema::table('budgets', function (Blueprint $table) {
            $table->boolean('alerts_enabled')->default(true)->after('notes');
            $table->unsignedTinyInteger('alert_threshold_warning')->default(75)->after('alerts_enabled');
            $table->unsignedTinyInteger('alert_threshold_critical')->default(90)->after('alert_threshold_warning');
            $table->timestamp('last_warning_sent_at')->nullable()->after('alert_threshold_critical');
            $table->timestamp('last_critical_sent_at')->nullable()->after('last_warning_sent_at');
            $table->timestamp('last_exceeded_sent_at')->nullable()->after('last_critical_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn([
                'alerts_enabled',
                'alert_threshold_warning',
                'alert_threshold_critical',
                'last_warning_sent_at',
                'last_critical_sent_at',
                'last_exceeded_sent_at',
            ]);
        });
    }
};
