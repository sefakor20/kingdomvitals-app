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
        Schema::table('visitors', function (Blueprint $table) {
            $table->timestamp('next_follow_up_at')->nullable()->after('assigned_to');
            $table->timestamp('last_follow_up_at')->nullable()->after('next_follow_up_at');
            $table->unsignedInteger('follow_up_count')->default(0)->after('last_follow_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['next_follow_up_at', 'last_follow_up_at', 'follow_up_count']);
        });
    }
};
