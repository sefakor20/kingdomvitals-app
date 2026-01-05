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
        Schema::create('children_checkin_security', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->constrained('attendance')->cascadeOnDelete();
            $table->foreignUuid('child_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignUuid('guardian_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('security_code', 6);
            $table->boolean('is_checked_out')->default(false);
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignUuid('checked_out_by')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->index(['attendance_id', 'security_code']);
            $table->index('child_member_id');
            $table->index('is_checked_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children_checkin_security');
    }
};
