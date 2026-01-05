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
            $table->foreignUuid('household_id')->nullable()->after('primary_branch_id')->constrained()->nullOnDelete();
            $table->string('household_role', 20)->nullable()->after('household_id');

            $table->index('household_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['household_id']);
            $table->dropColumn(['household_id', 'household_role']);
        });
    }
};
