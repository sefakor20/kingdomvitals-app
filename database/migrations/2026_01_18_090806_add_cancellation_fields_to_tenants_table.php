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
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('suspended_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'subscription_ends_at']);
        });
    }
};
