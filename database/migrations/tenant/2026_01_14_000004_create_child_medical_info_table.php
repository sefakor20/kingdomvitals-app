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
        Schema::create('child_medical_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('special_needs')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->string('doctor_name', 100)->nullable();
            $table->string('doctor_phone', 20)->nullable();
            $table->string('insurance_info', 255)->nullable();
            $table->text('emergency_instructions')->nullable();
            $table->timestamps();

            $table->unique('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_medical_info');
    }
};
