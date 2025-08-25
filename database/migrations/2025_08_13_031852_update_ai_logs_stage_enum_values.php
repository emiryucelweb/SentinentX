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
        // SQLite'da enum değişikliği için tabloyu yeniden oluştur
        if (config('database.default') === 'sqlite') {
            // SQLite'da enum değişikliği yapılamaz, tabloyu yeniden oluştur
            Schema::dropIfExists('ai_logs');
            Schema::create('ai_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('cycle_uuid')->index();
                $table->string('symbol')->index();
                $table->string('provider');
                $table->enum('stage', ['STAGE1', 'STAGE2', 'FINAL']);
                $table->enum('action', ['LONG', 'SHORT', 'HOLD', 'CLOSE', 'NO_TRADE'])->nullable();
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->json('input_ctx');
                $table->json('raw_output')->nullable();
                $table->integer('latency_ms')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->index(['cycle_uuid', 'provider', 'stage']);
            });
        } else {
            // PostgreSQL enum değişikliği başka migration'da yapılıyor
            // Bu kısım devre dışı - 2025_08_21_064148_fix_postgresql_ai_logs_enum.php kullanılıyor
            /*
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->enum('stage', ['STAGE1', 'STAGE2', 'FINAL'])->change();
            });
            */
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('ai_logs');
            Schema::create('ai_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('cycle_uuid')->index();
                $table->string('symbol')->index();
                $table->string('provider');
                $table->enum('stage', ['R1', 'R2', 'FINAL']);
                $table->enum('action', ['LONG', 'SHORT', 'HOLD', 'CLOSE', 'NO_TRADE'])->nullable();
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->json('input_ctx');
                $table->json('raw_output')->nullable();
                $table->integer('latency_ms')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->index(['cycle_uuid', 'provider', 'stage']);
            });
        } else {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->enum('stage', ['R1', 'R2', 'FINAL'])->change();
            });
        }
    }
};
