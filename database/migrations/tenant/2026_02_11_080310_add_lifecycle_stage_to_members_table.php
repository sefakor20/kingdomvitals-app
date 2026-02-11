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
            $table->string('lifecycle_stage', 30)->nullable()->after('status');
            $table->timestamp('lifecycle_stage_changed_at')->nullable()->after('lifecycle_stage');
            $table->json('lifecycle_stage_factors')->nullable()->after('lifecycle_stage_changed_at');

            $table->index('lifecycle_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['lifecycle_stage']);

            $table->dropColumn([
                'lifecycle_stage',
                'lifecycle_stage_changed_at',
                'lifecycle_stage_factors',
            ]);
        });
    }
};
