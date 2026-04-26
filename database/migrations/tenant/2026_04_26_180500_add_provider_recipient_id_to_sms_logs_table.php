<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('provider_recipient_id')->nullable()->after('provider_message_id');
            $table->index('provider_recipient_id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_recipient_id']);
            $table->dropColumn('provider_recipient_id');
        });
    }
};
