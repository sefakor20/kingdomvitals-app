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
        Schema::table('prayer_requests', function (Blueprint $table) {
            $table->string('urgency_level')->default('normal')->after('status');
            $table->decimal('priority_score', 5, 2)->nullable()->after('urgency_level');
            $table->json('ai_classification')->nullable()->after('priority_score');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_classification');

            $table->index(['branch_id', 'urgency_level']);
            $table->index(['urgency_level', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'urgency_level']);
            $table->dropIndex(['urgency_level', 'status']);

            $table->dropColumn([
                'urgency_level',
                'priority_score',
                'ai_classification',
                'ai_analyzed_at',
            ]);
        });
    }
};
