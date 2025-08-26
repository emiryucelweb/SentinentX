<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('lab_run_id')->nullable()->after('id');
            $table->decimal('equity', 15, 2)->nullable()->after('lab_run_id');
            $table->decimal('win_rate', 5, 2)->nullable()->after('sharpe');
            $table->decimal('avg_trade_pct', 8, 4)->nullable()->after('win_rate');
        });
    }

    public function down(): void
    {
        Schema::table('lab_metrics', function (Blueprint $table) {
            $table->dropColumn(['lab_run_id', 'equity', 'win_rate', 'avg_trade_pct']);
        });
    }
};
