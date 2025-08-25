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
            if (! Schema::hasColumn('ai_logs', 'trade_id')) {
                $t->unsignedBigInteger('trade_id')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('ai_logs', 'used_in_consensus')) {
                $t->boolean('used_in_consensus')->default(false)->after('trade_id');
            }
            if (! Schema::hasColumn('ai_logs', 'raw')) {
                $t->json('raw')->nullable()->after('used_in_consensus');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_logs')) {
            return;
        }

        Schema::table('ai_logs', function (Blueprint $t) {
            if (Schema::hasColumn('ai_logs', 'raw')) {
                $t->dropColumn('raw');
            }
            if (Schema::hasColumn('ai_logs', 'used_in_consensus')) {
                $t->dropColumn('used_in_consensus');
            }
            if (Schema::hasColumn('ai_logs', 'trade_id')) {
                $t->dropColumn('trade_id');
            }
        });
    }
};
