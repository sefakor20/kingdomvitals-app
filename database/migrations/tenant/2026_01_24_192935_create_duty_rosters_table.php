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
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('service_id')->nullable()->constrained()->onDelete('set null');
            $table->date('service_date');
            $table->string('theme', 255)->nullable();
            $table->foreignUuid('preacher_id')->nullable()->constrained('members')->onDelete('set null');
            $table->string('preacher_name', 100)->nullable();
            $table->foreignUuid('liturgist_id')->nullable()->constrained('members')->onDelete('set null');
            $table->string('liturgist_name', 100)->nullable();
            $table->json('hymn_numbers')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('draft'); // DutyRosterStatus enum
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('service_date');
            $table->index('status');
            $table->unique(['branch_id', 'service_id', 'service_date'], 'duty_roster_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_rosters');
    }
};
