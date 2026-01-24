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
        Schema::create('duty_roster_cluster', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('duty_roster_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('cluster_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['duty_roster_id', 'cluster_id']);
            $table->index('duty_roster_id');
            $table->index('cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_roster_cluster');
    }
};
