<?php

// ===== database/migrations/2025_08_11_000002_create_ai_logs_table.php =====

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip - ai_logs table already created in comprehensive testing tables
        if (Schema::hasTable('ai_logs')) {
            return;
        }

        Schema::create('ai_logs', function (Blueprint $t) {
            $t->id();
            $t->uuid('cycle_uuid')->index();
            $t->string('symbol')->index();
            $t->string('provider');
            $t->enum('stage', ['R1', 'R2', 'FINAL']);
            $t->enum('action', ['LONG', 'SHORT', 'HOLD', 'CLOSE', 'NO_TRADE'])->nullable();
            $t->unsignedTinyInteger('confidence')->nullable();
            $t->json('input_ctx');
            $t->json('raw_output')->nullable();
            $t->integer('latency_ms')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['cycle_uuid', 'provider', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
