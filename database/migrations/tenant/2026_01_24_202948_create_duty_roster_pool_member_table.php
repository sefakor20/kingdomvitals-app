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
        Schema::create('duty_roster_pool_member', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('duty_roster_pool_id')->constrained('duty_roster_pools')->onDelete('cascade');
            $table->foreignUuid('member_id')->constrained()->onDelete('cascade');
            $table->date('last_assigned_date')->nullable();
            $table->unsignedInteger('assignment_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['duty_roster_pool_id', 'member_id']);
            $table->index(['duty_roster_pool_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_roster_pool_member');
    }
};
