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
        Schema::create('pledges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('member_id')->constrained()->onDelete('cascade');
            $table->string('campaign_name')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('frequency', 20); // PHP Enum: PledgeFrequency (one_time, weekly, monthly, quarterly, yearly)
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('amount_fulfilled', 12, 2)->default(0);
            $table->string('status', 20)->default('active'); // PHP Enum: PledgeStatus (active, completed, cancelled, paused)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('member_id');
            $table->index('status');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pledges');
    }
};
