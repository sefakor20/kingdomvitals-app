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
        Schema::table('donations', function (Blueprint $table) {
            $table->string('donor_email')->nullable()->after('donor_name');
            $table->string('donor_phone')->nullable()->after('donor_email');
            $table->string('paystack_customer_code')->nullable()->after('donor_phone');
            $table->boolean('is_recurring')->default(false)->after('is_anonymous');
            $table->string('recurring_interval')->nullable()->after('is_recurring'); // weekly, monthly, yearly
            $table->string('paystack_subscription_code')->nullable()->after('recurring_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn([
                'donor_email',
                'donor_phone',
                'paystack_customer_code',
                'is_recurring',
                'recurring_interval',
                'paystack_subscription_code',
            ]);
        });
    }
};
