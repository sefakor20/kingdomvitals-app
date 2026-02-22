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
        Schema::table('events', function (Blueprint $table) {
            // Recurrence configuration (only set on parent/template events)
            $table->string('recurrence_pattern')->nullable()->after('notes');
            $table->date('recurrence_ends_at')->nullable()->after('recurrence_pattern');
            $table->integer('recurrence_count')->nullable()->after('recurrence_ends_at');

            // Link child events to parent template
            $table->foreignUuid('parent_event_id')->nullable()->after('recurrence_count')
                ->constrained('events')->nullOnDelete();
            $table->integer('occurrence_index')->nullable()->after('parent_event_id');

            $table->index('parent_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['parent_event_id']);
            $table->dropIndex(['parent_event_id']);
            $table->dropColumn([
                'recurrence_pattern',
                'recurrence_ends_at',
                'recurrence_count',
                'parent_event_id',
                'occurrence_index',
            ]);
        });
    }
};
