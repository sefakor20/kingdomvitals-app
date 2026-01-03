<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignUuid('recurring_expense_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('recurring_expenses')
                ->onDelete('set null');

            $table->index('recurring_expense_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['recurring_expense_id']);
            $table->dropIndex(['recurring_expense_id']);
            $table->dropColumn('recurring_expense_id');
        });
    }
};
