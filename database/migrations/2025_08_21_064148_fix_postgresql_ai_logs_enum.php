<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL enum değişikliği için özel migration
     */
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            // PostgreSQL için raw SQL ile enum değiştir
            DB::statement('ALTER TABLE ai_logs ALTER COLUMN stage TYPE varchar(255)');
            // Constraint varsa önce kaldır, sonra yenisini ekle
            DB::statement('ALTER TABLE ai_logs DROP CONSTRAINT IF EXISTS ai_logs_stage_check');
            DB::statement("ALTER TABLE ai_logs ADD CONSTRAINT ai_logs_stage_check CHECK (stage IN ('STAGE1', 'STAGE2', 'FINAL'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE ai_logs DROP CONSTRAINT IF EXISTS ai_logs_stage_check');
            DB::statement('ALTER TABLE ai_logs ALTER COLUMN stage TYPE varchar(255)');
            DB::statement("ALTER TABLE ai_logs ADD CONSTRAINT ai_logs_stage_check CHECK (stage IN ('R1', 'R2', 'FINAL'))");
        }
    }
};
