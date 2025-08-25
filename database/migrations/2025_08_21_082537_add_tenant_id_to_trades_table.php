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
        Schema::table('trades', function (Blueprint $table) {
            if (! Schema::hasColumn('trades', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id');
            }

            // Add foreign key constraint (Laravel will handle if it exists)
            try {
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
            $table->index(['tenant_id', 'status']);
        });

        // Enable Row Level Security for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE trades ENABLE ROW LEVEL SECURITY');

            // Policy: Users can only access trades from their tenant
            DB::statement('
                CREATE POLICY trade_tenant_isolation ON trades
                USING (tenant_id = current_setting(\'app.tenant_id\', true)::bigint)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS trade_tenant_isolation ON trades');
        }

        Schema::table('trades', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn('tenant_id');
        });
    }
};
