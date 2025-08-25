<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_logs')) {
            return;
        }
        Schema::table('ai_logs', function (Blueprint $t) {
            if (! Schema::hasColumn('ai_logs', 'round')) {
                $t->unsignedTinyInteger('round')->nullable()->after('model');
            } // 1|2
            if (! Schema::hasColumn('ai_logs', 'cycle_id')) {
                $t->string('cycle_id', 64)->nullable()->index()->after('round');
            }
            if (! Schema::hasColumn('ai_logs', 'latency_ms')) {
                $t->unsignedInteger('latency_ms')->nullable()->after('confidence');
            }
            if (! Schema::hasColumn('ai_logs', 'error_code')) {
                $t->string('error_code', 64)->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_logs')) {
            return;
        }
        Schema::table('ai_logs', function (Blueprint $t) {
            foreach (['round', 'cycle_id', 'latency_ms', 'error_code'] as $c) {
                if (Schema::hasColumn('ai_logs', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
