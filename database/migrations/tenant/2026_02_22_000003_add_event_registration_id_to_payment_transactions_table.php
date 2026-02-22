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
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->foreignUuid('event_registration_id')
                ->nullable()
                ->after('donation_id')
                ->constrained('event_registrations')
                ->onDelete('set null');

            $table->index('event_registration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['event_registration_id']);
            $table->dropIndex(['event_registration_id']);
            $table->dropColumn('event_registration_id');
        });
    }
};
