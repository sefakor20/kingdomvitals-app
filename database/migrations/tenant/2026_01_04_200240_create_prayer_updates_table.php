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
        Schema::create('prayer_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prayer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();

            $table->text('content');

            $table->timestamps();

            $table->index(['prayer_request_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_updates');
    }
};
