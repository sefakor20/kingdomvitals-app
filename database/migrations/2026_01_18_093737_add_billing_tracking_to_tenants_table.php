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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->nullable()->after('subscription_ends_at');
            $table->date('current_period_start')->nullable()->after('billing_cycle');
            $table->date('current_period_end')->nullable()->after('current_period_start');
            $table->decimal('account_credit', 15, 2)->default(0)->after('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'billing_cycle',
                'current_period_start',
                'current_period_end',
                'account_credit',
            ]);
        });
    }
};
