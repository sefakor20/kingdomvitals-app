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
        Schema::create('visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('visit_date');
            $table->string('status', 20)->default('new'); // PHP Enum: VisitorStatus
            $table->string('how_did_you_hear')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('members')->onDelete('set null');
            $table->boolean('is_converted')->default(false);
            $table->foreignUuid('converted_member_id')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();

            $table->index('branch_id');
            $table->index('status');
            $table->index('visit_date');
            $table->index('is_converted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
