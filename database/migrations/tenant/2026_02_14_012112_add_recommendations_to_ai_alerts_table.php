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
        Schema::table('ai_alerts', function (Blueprint $table): void {
            $table->json('recommendations')->nullable()->after('data');
            $table->boolean('recommendation_acted_on')->nullable()->after('recommendations');
            $table->timestamp('recommendation_acted_at')->nullable()->after('recommendation_acted_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_alerts', function (Blueprint $table): void {
            $table->dropColumn([
                'recommendations',
                'recommendation_acted_on',
                'recommendation_acted_at',
            ]);
        });
    }
};
