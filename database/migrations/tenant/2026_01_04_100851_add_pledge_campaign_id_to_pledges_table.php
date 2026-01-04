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
        Schema::table('pledges', function (Blueprint $table) {
            $table->foreignUuid('pledge_campaign_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('pledge_campaigns')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pledges', function (Blueprint $table) {
            $table->dropForeign(['pledge_campaign_id']);
            $table->dropColumn('pledge_campaign_id');
        });
    }
};
