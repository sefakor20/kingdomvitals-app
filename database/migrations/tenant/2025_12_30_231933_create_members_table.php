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
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('primary_branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable(); // PHP Enum: Gender
            $table->string('marital_status', 20)->nullable(); // PHP Enum: MaritalStatus
            $table->string('status', 20)->default('active'); // PHP Enum: MembershipStatus
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('country')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('baptized_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();

            $table->index('primary_branch_id');
            $table->index('status');
            $table->index('email');
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
