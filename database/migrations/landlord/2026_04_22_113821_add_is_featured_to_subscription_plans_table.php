<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
