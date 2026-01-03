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
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category', 50);
            $table->decimal('allocated_amount', 12, 2);
            $table->integer('fiscal_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();

            $table->index(['branch_id', 'fiscal_year']);
            $table->index(['branch_id', 'category', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
