<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_metrics', function (Blueprint $table) {
            // Eski unique constraint'i kaldır
            $table->dropUnique(['as_of']);
            // Yeni composite unique constraint ekle
            $table->unique(['lab_run_id', 'as_of']);
        });
    }

    public function down(): void
    {
        Schema::table('lab_metrics', function (Blueprint $table) {
            $table->dropUnique(['lab_run_id', 'as_of']);
            $table->unique(['as_of']);
        });
    }
};
