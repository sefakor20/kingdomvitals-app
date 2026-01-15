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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('max_households')->nullable()->after('sms_credits_monthly');
            $table->integer('max_clusters')->nullable()->after('max_households');
            $table->integer('max_visitors')->nullable()->after('max_clusters');
            $table->integer('max_equipment')->nullable()->after('max_visitors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_households',
                'max_clusters',
                'max_visitors',
                'max_equipment',
            ]);
        });
    }
};
