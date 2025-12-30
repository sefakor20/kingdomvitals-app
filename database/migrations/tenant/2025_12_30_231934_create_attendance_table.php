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
        Schema::create('attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->foreignUuid('member_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('visitor_id')->nullable()->constrained()->onDelete('cascade');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('check_in_method', 20)->nullable(); // PHP Enum: CheckInMethod (manual, qr, kiosk)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('service_id');
            $table->index('branch_id');
            $table->index('date');
            $table->index('member_id');
            $table->index('visitor_id');
            $table->unique(['service_id', 'date', 'member_id'], 'unique_member_attendance');
            $table->unique(['service_id', 'date', 'visitor_id'], 'unique_visitor_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
