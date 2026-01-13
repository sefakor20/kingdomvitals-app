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
            $table->foreignUuid('age_group_id')
                ->nullable()
                ->after('household_id')
                ->constrained('age_groups')
                ->nullOnDelete();

            $table->index('age_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['age_group_id']);
            $table->dropIndex(['age_group_id']);
            $table->dropColumn('age_group_id');
        });
    }
};
