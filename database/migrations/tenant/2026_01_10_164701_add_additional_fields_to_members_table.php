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
        Schema::table('members', function (Blueprint $table) {
            $table->string('previous_congregation', 255)->nullable()->after('notes');
            $table->string('gps_address', 100)->nullable()->after('country');
            $table->string('profession', 100)->nullable()->after('marital_status');
            $table->string('employment_status', 20)->nullable()->after('profession');
            $table->string('maiden_name', 100)->nullable()->after('last_name');
            $table->string('hometown', 100)->nullable()->after('country');
            $table->date('confirmation_date')->nullable()->after('baptized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'previous_congregation',
                'gps_address',
                'profession',
                'employment_status',
                'maiden_name',
                'hometown',
                'confirmation_date',
            ]);
        });
    }
};
