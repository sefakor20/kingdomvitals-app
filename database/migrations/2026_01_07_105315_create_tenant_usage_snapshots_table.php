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
        Schema::create('tenant_usage_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Usage metrics
            $table->integer('total_members')->default(0);
            $table->integer('active_members')->default(0);
            $table->integer('total_branches')->default(0);
            $table->integer('sms_sent_this_month')->default(0);
            $table->decimal('sms_cost_this_month', 10, 2)->default(0);
            $table->decimal('donations_this_month', 15, 2)->default(0);
            $table->integer('donation_count_this_month')->default(0);
            $table->integer('attendance_this_month')->default(0);
            $table->integer('visitors_this_month')->default(0);
            $table->integer('visitor_conversions_this_month')->default(0);

            // Feature usage
            $table->json('active_modules')->nullable();

            // Quota usage percentages
            $table->decimal('member_quota_usage_percent', 5, 2)->nullable();
            $table->decimal('branch_quota_usage_percent', 5, 2)->nullable();
            $table->decimal('sms_quota_usage_percent', 5, 2)->nullable();
            $table->decimal('storage_quota_usage_percent', 5, 2)->nullable();

            $table->date('snapshot_date');
            $table->timestamps();

            $table->unique(['tenant_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usage_snapshots');
    }
};
