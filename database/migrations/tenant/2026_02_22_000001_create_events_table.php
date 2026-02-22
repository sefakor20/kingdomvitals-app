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
        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('organizer_id')->nullable()->constrained('members')->onDelete('set null');

            // Basic info
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('event_type', 30);
            $table->string('category', 50)->nullable();

            // Schedule
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location', 150);
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Capacity & Registration
            $table->integer('capacity')->nullable();
            $table->boolean('allow_registration')->default(true);
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();

            // Pricing
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 8, 2)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->boolean('requires_ticket')->default(true);

            // Status
            $table->string('status', 20)->default('draft');
            $table->boolean('is_public')->default(true);
            $table->string('visibility', 20)->default('public');

            // Additional
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('branch_id');
            $table->index('organizer_id');
            $table->index('starts_at');
            $table->index('status');
            $table->index('event_type');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
