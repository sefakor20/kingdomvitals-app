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
        Schema::create('cluster_meeting_attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cluster_meeting_id')->constrained('cluster_meetings')->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->boolean('attended')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['cluster_meeting_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cluster_meeting_attendance');
    }
};
