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
        Schema::create('user_branch_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // References user in central database
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('role', 30); // PHP Enum: BranchRole (admin, manager, staff, volunteer)
            $table->boolean('is_primary')->default(false);
            $table->json('permissions')->nullable(); // Additional granular permissions
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index('user_id');
            $table->index('branch_id');
            $table->index('role');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_branch_access');
    }
};
