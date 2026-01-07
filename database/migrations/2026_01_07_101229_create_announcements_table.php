<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('super_admin_id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('target_audience', 50);
            $table->json('specific_tenant_ids')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('successful_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();

            $table->foreign('super_admin_id')->references('id')->on('super_admins')->cascadeOnDelete();
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
