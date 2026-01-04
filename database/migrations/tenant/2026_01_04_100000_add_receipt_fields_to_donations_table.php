<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique()->after('reference_number');
            $table->timestamp('receipt_sent_at')->nullable()->after('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'receipt_sent_at']);
        });
    }
};
