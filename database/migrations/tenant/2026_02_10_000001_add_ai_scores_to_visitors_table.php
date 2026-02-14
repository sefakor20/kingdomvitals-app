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
        Schema::table('visitors', function (Blueprint $table) {
            $table->decimal('conversion_score', 5, 2)->nullable()->after('is_converted');
            $table->json('conversion_factors')->nullable()->after('conversion_score');
            $table->timestamp('conversion_score_calculated_at')->nullable()->after('conversion_factors');

            $table->index('conversion_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['conversion_score']);
            $table->dropColumn([
                'conversion_score',
                'conversion_factors',
                'conversion_score_calculated_at',
            ]);
        });
    }
};
