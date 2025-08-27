<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert all timestamp columns to timestamptz
        $tables = [
            'users', 'password_reset_tokens', 'ai_decision_logs', 'position_logs',
            'performance_summaries', 'backtest_data', 'trades', 'ai_logs',
            'consensus_decisions', 'alerts', 'ai_providers', 'lab_trades',
            'lab_metrics', 'lab_runs', 'usage_counters', 'plans', 'subscriptions',
            'tenants', 'audit_logs', 'positions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if (Schema::hasColumn($table, 'created_at')) {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN created_at TYPE timestamptz USING created_at AT TIME ZONE 'UTC'");
                }
                if (Schema::hasColumn($table, 'updated_at')) {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN updated_at TYPE timestamptz USING updated_at AT TIME ZONE 'UTC'");
                }
            }
        }

        // Handle special timestamp columns in jobs and job_batches
        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'created_at')) {
            DB::statement("ALTER TABLE jobs ALTER COLUMN created_at TYPE timestamptz USING to_timestamp(created_at) AT TIME ZONE 'UTC'");
        }

        if (Schema::hasTable('job_batches') && Schema::hasColumn('job_batches', 'created_at')) {
            DB::statement("ALTER TABLE job_batches ALTER COLUMN created_at TYPE timestamptz USING to_timestamp(created_at) AT TIME ZONE 'UTC'");
        }

        // Create orders table with idempotency
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 20);
            $table->enum('side', ['BUY', 'SELL']);
            $table->enum('order_type', ['MARKET', 'LIMIT', 'STOP', 'STOP_LIMIT']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('limit_price', 20, 8)->nullable();
            $table->decimal('stop_price', 20, 8)->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('order_link_id')->nullable();
            $table->string('correlation_id')->nullable()->unique();
            $table->enum('status', ['PENDING', 'FILLED', 'PARTIALLY_FILLED', 'CANCELLED', 'REJECTED']);
            $table->string('exchange_order_id')->nullable();
            $table->json('exchange_response')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            // Indexes for performance
            $table->index(['symbol', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['tenant_id', 'symbol']);
        });

        // Create fills table for order execution tracking
        Schema::create('fills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('trade_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('quantity', 20, 8);
            $table->decimal('price', 20, 8);
            $table->decimal('commission', 20, 8)->default(0);
            $table->string('commission_asset', 10)->nullable();
            $table->string('exchange_fill_id');
            $table->timestampTz('filled_at');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['order_id', 'filled_at']);
            $table->unique('exchange_fill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('fills');
        Schema::dropIfExists('orders');

        // Revert timestamp columns back to timestamp without time zone
        $tables = [
            'users', 'password_reset_tokens', 'ai_decision_logs', 'position_logs',
            'performance_summaries', 'backtest_data', 'trades', 'ai_logs',
            'consensus_decisions', 'alerts', 'ai_providers', 'lab_trades',
            'lab_metrics', 'lab_runs', 'usage_counters', 'plans', 'subscriptions',
            'tenants', 'audit_logs', 'positions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if (Schema::hasColumn($table, 'created_at')) {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN created_at TYPE timestamp");
                }
                if (Schema::hasColumn($table, 'updated_at')) {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN updated_at TYPE timestamp");
                }
            }
        }

        // Revert special timestamp columns
        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'created_at')) {
            DB::statement('ALTER TABLE jobs ALTER COLUMN created_at TYPE integer USING extract(epoch from created_at)');
        }

        if (Schema::hasTable('job_batches') && Schema::hasColumn('job_batches', 'created_at')) {
            DB::statement('ALTER TABLE job_batches ALTER COLUMN created_at TYPE integer USING extract(epoch from created_at)');
        }
    }
};
