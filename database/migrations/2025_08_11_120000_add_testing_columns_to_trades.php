<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns needed for comprehensive testing
     */
    public function up(): void
    {
        // Skip in testing environment - tables are created manually in tests
        if (app()->environment('testing')) {
            return;
        }

        Schema::table('trades', function (Blueprint $table) {
            // Add realized_pnl alias for testing compatibility
            if (! Schema::hasColumn('trades', 'realized_pnl')) {
                $table->decimal('realized_pnl', 18, 8)->nullable()->after('pnl_realized');
            }

            // Add tenant_id for multi-tenant testing
            if (! Schema::hasColumn('trades', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->index();
            }

            // Add user_id for GDPR testing
            if (! Schema::hasColumn('trades', 'user_id')) {
                $table->string('user_id')->nullable()->after('tenant_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            // Check if columns exist before dropping (SQLite compatibility)
            if (Schema::hasColumn('trades', 'realized_pnl')) {
                $table->dropColumn('realized_pnl');
            }
            if (Schema::hasColumn('trades', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('trades', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
