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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->boolean('is_main')->default(false);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('country')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->string('status', 20)->default('active'); // PHP Enum: BranchStatus
            $table->string('logo_url')->nullable();
            $table->string('color_primary', 7)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('slug');
            $table->index('status');
            $table->index('is_main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
