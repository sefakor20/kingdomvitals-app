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
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('giving_capacity_score', 5, 2)->nullable()->after('giving_analyzed_at');
            $table->decimal('giving_potential_gap', 10, 2)->nullable()->after('giving_capacity_score');
            $table->json('giving_capacity_factors')->nullable()->after('giving_potential_gap');
            $table->timestamp('giving_capacity_analyzed_at')->nullable()->after('giving_capacity_factors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'giving_capacity_score',
                'giving_potential_gap',
                'giving_capacity_factors',
                'giving_capacity_analyzed_at',
            ]);
        });
    }
};
