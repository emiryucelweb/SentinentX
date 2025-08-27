<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL için enum değeri ekleme
        if (config('database.default') === 'pgsql') {
            // Önce enum tipini kontrol et
            $enumExists = DB::select("
                SELECT 1 FROM pg_type 
                WHERE typname = 'consensus_decisions_final_action'
            ");
            
            if ($enumExists) {
                // Enum tipine NO_TRADE ekle
                DB::statement("
                    ALTER TYPE consensus_decisions_final_action 
                    ADD VALUE IF NOT EXISTS 'NO_TRADE'
                ");
            } else {
                // Enum tipi yoksa sütunu güncelle
                DB::statement("
                    ALTER TABLE consensus_decisions 
                    ALTER COLUMN final_action TYPE VARCHAR(20)
                ");
                
                // Sonra tekrar enum yap
                DB::statement("
                    ALTER TABLE consensus_decisions 
                    ALTER COLUMN final_action TYPE consensus_decisions_final_action 
                    USING final_action::consensus_decisions_final_action
                ");
            }
        } else {
            // MySQL/SQLite için
            Schema::table('consensus_decisions', function (Blueprint $table) {
                $table->enum('final_action', ['LONG', 'SHORT', 'HOLD', 'CLOSE', 'NO_TRADE'])->change();
            });
        }
    }

    public function down(): void
    {
        // Rollback işlemi - NO_TRADE değerini kaldır
        if (config('database.default') === 'pgsql') {
            // PostgreSQL'de enum değerini kaldırmak zordur, bu yüzden sadece uyarı ver
            // Gerçek üretimde bu tür değişiklikler dikkatli planlanmalı
        } else {
            Schema::table('consensus_decisions', function (Blueprint $table) {
                $table->enum('final_action', ['LONG', 'SHORT', 'HOLD', 'CLOSE'])->change();
            });
        }
    }
};
