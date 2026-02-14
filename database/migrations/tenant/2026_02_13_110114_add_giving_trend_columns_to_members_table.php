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
        Schema::table('members', function (Blueprint $table): void {
            $table->unsignedTinyInteger('giving_consistency_score')->nullable()->after('lifecycle_stage_factors');
            $table->decimal('giving_growth_rate', 6, 2)->nullable()->after('giving_consistency_score');
            $table->string('donor_tier', 20)->nullable()->after('giving_growth_rate');
            $table->string('giving_trend', 20)->nullable()->after('donor_tier');
            $table->timestamp('giving_analyzed_at')->nullable()->after('giving_trend');

            $table->index(['primary_branch_id', 'donor_tier']);
            $table->index(['primary_branch_id', 'giving_trend']);
            $table->index(['primary_branch_id', 'giving_consistency_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex(['primary_branch_id', 'donor_tier']);
            $table->dropIndex(['primary_branch_id', 'giving_trend']);
            $table->dropIndex(['primary_branch_id', 'giving_consistency_score']);

            $table->dropColumn([
                'giving_consistency_score',
                'giving_growth_rate',
                'donor_tier',
                'giving_trend',
                'giving_analyzed_at',
            ]);
        });
    }
};
