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
        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->decimal('proration_credit', 15, 2)->default(0)->after('discount_amount');
            $table->uuid('previous_plan_id')->nullable()->after('subscription_plan_id');
            $table->string('change_type', 20)->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'proration_credit',
                'previous_plan_id',
                'change_type',
            ]);
        });
    }
};
