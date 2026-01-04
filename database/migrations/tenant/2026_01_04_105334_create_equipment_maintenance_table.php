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
        Schema::create('equipment_maintenance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('equipment_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('scheduled');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->text('description');
            $table->text('findings')->nullable();
            $table->text('work_performed')->nullable();
            $table->string('service_provider')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->string('condition_before', 20)->nullable();
            $table->string('condition_after', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('equipment_id');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance');
    }
};
