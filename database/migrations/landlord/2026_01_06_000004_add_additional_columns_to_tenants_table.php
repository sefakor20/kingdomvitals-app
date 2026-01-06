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
            if (! Schema::hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('tenants', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }
            if (! Schema::hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes()->after('suspension_reason');
            }
            if (! Schema::hasColumn('tenants', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('name');
            }
            if (! Schema::hasColumn('tenants', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('contact_phone');
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
                'suspended_at',
                'suspension_reason',
                'contact_email',
                'contact_phone',
                'address',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
