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
        Schema::create('clusters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('cluster_type', 30); // PHP Enum: ClusterType (cell_group, house_fellowship, zone, district)
            $table->text('description')->nullable();
            $table->foreignUuid('leader_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignUuid('assistant_leader_id')->nullable()->constrained('members')->onDelete('set null');
            $table->string('meeting_day', 20)->nullable();
            $table->time('meeting_time')->nullable();
            $table->string('meeting_location')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('cluster_type');
            $table->index('leader_id');
            $table->index('is_active');
        });

        // Pivot table for cluster members
        Schema::create('cluster_member', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cluster_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('member_id')->constrained()->onDelete('cascade');
            $table->string('role', 20)->default('member'); // PHP Enum: ClusterRole (leader, assistant, member)
            $table->date('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['cluster_id', 'member_id']);
            $table->index('cluster_id');
            $table->index('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cluster_member');
        Schema::dropIfExists('clusters');
    }
};
