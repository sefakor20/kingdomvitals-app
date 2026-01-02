<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // Drop old foreign key constraint referencing members
            $table->dropForeign(['sent_by']);

            // Add new foreign key constraint referencing users
            $table->foreign('sent_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // Drop the users foreign key
            $table->dropForeign(['sent_by']);

            // Restore the members foreign key
            $table->foreign('sent_by')
                ->references('id')
                ->on('members')
                ->onDelete('set null');
        });
    }
};
