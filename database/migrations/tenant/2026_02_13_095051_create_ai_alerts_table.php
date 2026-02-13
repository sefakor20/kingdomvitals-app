<?php

declare(strict_types=1);

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
        Schema::create('ai_alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type');
            $table->string('severity');
            $table->string('title');
            $table->text('description');
            $table->uuidMorphs('alertable');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'alert_type', 'created_at']);
            $table->index(['branch_id', 'is_read']);
            $table->index(['branch_id', 'is_acknowledged']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_alerts');
    }
};
