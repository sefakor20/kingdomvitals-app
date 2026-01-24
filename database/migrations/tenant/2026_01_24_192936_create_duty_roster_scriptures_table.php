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
        Schema::create('duty_roster_scriptures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('duty_roster_id')->constrained()->onDelete('cascade');
            $table->string('reference', 100);
            $table->string('reading_type', 30)->nullable(); // ScriptureReadingType enum
            $table->foreignUuid('reader_id')->nullable()->constrained('members')->onDelete('set null');
            $table->string('reader_name', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('duty_roster_id');
            $table->index('reader_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_roster_scriptures');
    }
};
