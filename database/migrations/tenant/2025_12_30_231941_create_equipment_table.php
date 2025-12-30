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
        Schema::create('equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category', 50); // PHP Enum: EquipmentCategory (audio, video, musical, furniture, computer, other)
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->string('condition', 20)->default('good'); // PHP Enum: EquipmentCondition (excellent, good, fair, poor, out_of_service)
            $table->string('location')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('members')->onDelete('set null');
            $table->date('warranty_expiry')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('category');
            $table->index('condition');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
