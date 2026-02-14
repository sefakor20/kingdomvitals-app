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
            // Add missing columns for Tenant model
            if (! Schema::hasColumn('tenants', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('name');
            }
            if (! Schema::hasColumn('tenants', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('contact_phone');
            }
            if (! Schema::hasColumn('tenants', 'logo')) {
                $table->json('logo')->nullable()->after('address');
            }
            if (! Schema::hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('tenants', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }
            if (! Schema::hasColumn('tenants', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('subscription_id');
            }
            if (! Schema::hasColumn('tenants', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('cancellation_reason');
            }
            if (! Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->nullable()->after('subscription_ends_at');
            }
            if (! Schema::hasColumn('tenants', 'current_period_start')) {
                $table->date('current_period_start')->nullable()->after('billing_cycle');
            }
            if (! Schema::hasColumn('tenants', 'current_period_end')) {
                $table->date('current_period_end')->nullable()->after('current_period_start');
            }
            if (! Schema::hasColumn('tenants', 'account_credit')) {
                $table->decimal('account_credit', 15, 2)->default(0)->after('current_period_end');
            }
            if (! Schema::hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email',
                'contact_phone',
                'address',
                'logo',
                'suspended_at',
                'suspension_reason',
                'cancelled_at',
                'cancellation_reason',
                'subscription_ends_at',
                'billing_cycle',
                'current_period_start',
                'current_period_end',
                'account_credit',
                'deleted_at',
            ]);
        });
    }
};
