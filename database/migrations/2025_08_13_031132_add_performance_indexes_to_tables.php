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
        // ai_logs: cycle_uuid, symbol, stage, created_at birleşik indeks
        Schema::table('ai_logs', function (Blueprint $table) {
            if (! Schema::hasIndex('ai_logs', 'ai_logs_cycle_symbol_stage_created_idx')) {
                $table->index(['cycle_uuid', 'symbol', 'stage', 'created_at'], 'ai_logs_cycle_symbol_stage_created_idx');
            }
        });

        // consensus_decisions: symbol, created_at indeks (cycle_uuid zaten unique)
        Schema::table('consensus_decisions', function (Blueprint $table) {
            if (! Schema::hasIndex('consensus_decisions', 'consensus_decisions_symbol_created_idx')) {
                $table->index(['symbol', 'created_at'], 'consensus_decisions_symbol_created_idx');
            }
        });

        // trades: status, symbol, status indeks
        Schema::table('trades', function (Blueprint $table) {
            if (! Schema::hasIndex('trades', 'trades_status_symbol_idx')) {
                $table->index(['status', 'symbol'], 'trades_status_symbol_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            if (Schema::hasIndex('ai_logs', 'ai_logs_cycle_symbol_stage_created_idx')) {
                $table->dropIndex('ai_logs_cycle_symbol_stage_created_idx');
            }
        });

        Schema::table('consensus_decisions', function (Blueprint $table) {
            if (Schema::hasIndex('consensus_decisions', 'consensus_decisions_symbol_created_idx')) {
                $table->dropIndex('consensus_decisions_symbol_created_idx');
            }
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex('trades_status_symbol_idx');
        });
    }
};
