<?php

declare(strict_types=1);

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
        // PostgreSQL-specific optimizations for SaaS multi-tenancy
        if (DB::getDriverName() === 'pgsql') {
            $this->createPostgreSQLOptimizations();
        }

        // Add composite indexes for tenant-aware queries
        $this->addTenantOptimizedIndexes();

        // Add performance monitoring tables
        $this->createPerformanceMonitoringTables();

        // Add SaaS-specific columns if missing
        $this->addSaaSColumns();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop performance monitoring tables
        Schema::dropIfExists('query_performance_logs');
        Schema::dropIfExists('tenant_performance_metrics');

        // Remove SaaS-specific indexes
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_trades_tenant_status_created');
            DB::statement('DROP INDEX IF EXISTS idx_users_tenant_active');
            DB::statement('DROP INDEX IF EXISTS idx_usage_counters_tenant_period');
            DB::statement('DROP INDEX IF EXISTS idx_subscriptions_tenant_status');
        }
    }

    /**
     * Create PostgreSQL-specific optimizations
     */
    private function createPostgreSQLOptimizations(): void
    {
        // Enable pg_stat_statements for query performance monitoring
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_stat_statements");

        // Create tenant schemas if they don't exist
        DB::statement("CREATE SCHEMA IF NOT EXISTS tenant_default");

        // Set search path for multi-tenant queries
        DB::statement("ALTER DATABASE " . DB::getDatabaseName() . " SET search_path = public, tenant_default");

        // Create partial indexes for better performance
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_trades_tenant_open 
            ON trades (tenant_id, created_at DESC) 
            WHERE status = 'OPEN'
        ");

        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_tenant_active 
            ON users (tenant_id, id) 
            WHERE email_verified_at IS NOT NULL
        ");

        // Create GIN index for JSONB meta columns
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_trades_meta_gin 
            ON trades USING GIN (meta)
        ");

        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tenants_settings_gin 
            ON tenants USING GIN (settings)
        ");
    }

    /**
     * Add tenant-optimized composite indexes
     */
    private function addTenantOptimizedIndexes(): void
    {
        // Trades table - most queried patterns (only if table exists)
        if (Schema::hasTable('trades')) {
            Schema::table('trades', function (Blueprint $table) {
                if (!$this->indexExists('trades', 'idx_trades_tenant_status_created')) {
                    $table->index(['tenant_id', 'status', 'created_at'], 'idx_trades_tenant_status_created');
                }
                if (!$this->indexExists('trades', 'idx_trades_tenant_symbol_status')) {
                    $table->index(['tenant_id', 'symbol', 'status'], 'idx_trades_tenant_symbol_status');
                }
                if (!$this->indexExists('trades', 'idx_trades_tenant_opened')) {
                    $table->index(['tenant_id', 'opened_at'], 'idx_trades_tenant_opened');
                }
            });
        }

        // Usage counters - billing queries (only if table exists)
        if (Schema::hasTable('usage_counters')) {
            Schema::table('usage_counters', function (Blueprint $table) {
                if (!$this->indexExists('usage_counters', 'idx_usage_counters_tenant_period')) {
                    $table->index(['tenant_id', 'usage_type', 'period_start'], 'idx_usage_counters_tenant_period');
                }
                if (!$this->indexExists('usage_counters', 'idx_usage_counters_tenant_last_used')) {
                    $table->index(['tenant_id', 'last_used_at'], 'idx_usage_counters_tenant_last_used');
                }
            });
        }

        // Subscriptions - billing and access control (only if table exists)
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!$this->indexExists('subscriptions', 'idx_subscriptions_user_status_ends')) {
                    $table->index(['user_id', 'status', 'ends_at'], 'idx_subscriptions_user_status_ends');
                }
                if (!$this->indexExists('subscriptions', 'idx_subscriptions_plan_status_created')) {
                    $table->index(['plan', 'status', 'created_at'], 'idx_subscriptions_plan_status_created');
                }
            });
        }

        // AI logs - consensus queries
        if (Schema::hasTable('ai_logs')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_at'], 'idx_ai_logs_tenant_created');
                $table->index(['symbol', 'created_at'], 'idx_ai_logs_symbol_created');
            });
        }
    }

    /**
     * Create performance monitoring tables
     */
    private function createPerformanceMonitoringTables(): void
    {
        // Query performance monitoring
        Schema::create('query_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('query_type', 100);
            $table->text('query_hash');
            $table->decimal('execution_time_ms', 10, 3);
            $table->integer('rows_examined')->nullable();
            $table->integer('rows_returned')->nullable();
            $table->json('query_metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'created_at']);
            $table->index(['query_type', 'execution_time_ms']);
            $table->index(['created_at']);
        });

        // Tenant performance metrics
        Schema::create('tenant_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->date('metric_date');
            $table->integer('api_requests_count')->default(0);
            $table->integer('trades_count')->default(0);
            $table->integer('ai_requests_count')->default(0);
            $table->decimal('avg_response_time_ms', 8, 3)->default(0);
            $table->decimal('p95_response_time_ms', 8, 3)->default(0);
            $table->integer('error_count')->default(0);
            $table->decimal('error_rate_pct', 5, 2)->default(0);
            $table->json('additional_metrics')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'metric_date']);
            $table->index(['metric_date', 'api_requests_count']);
            $table->index(['tenant_id', 'metric_date']);
        });
    }

    /**
     * Add SaaS-specific columns if missing
     */
    private function addSaaSColumns(): void
    {
        // Add tenant_id to tables that might be missing it
        $tablesToCheck = ['market_data', 'alerts', 'audit_logs'];

        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('tenant_id')->nullable()->after('id');
                    $table->index(['tenant_id', 'created_at']);
                });
            }
        }

        // Add SaaS metadata to tenants table
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'subscription_tier')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('subscription_tier', 50)->nullable()->after('status');
                $table->integer('monthly_api_quota')->nullable()->after('subscription_tier');
                $table->integer('current_api_usage')->default(0)->after('monthly_api_quota');
                $table->timestamp('quota_reset_at')->nullable()->after('current_api_usage');
                $table->json('feature_flags')->nullable()->after('quota_reset_at');
                $table->decimal('overage_rate', 8, 4)->nullable()->after('feature_flags');
                $table->boolean('is_suspended')->default(false)->after('overage_rate');
                $table->timestamp('suspended_at')->nullable()->after('is_suspended');
                $table->text('suspension_reason')->nullable()->after('suspended_at');

                $table->index(['subscription_tier', 'is_suspended']);
                $table->index(['quota_reset_at']);
            });
        }

        // Add billing metadata to subscriptions table
        if (Schema::hasTable('subscriptions') && !Schema::hasColumn('subscriptions', 'billing_cycle')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('billing_cycle', 20)->default('monthly')->after('plan');
                $table->decimal('monthly_price', 10, 2)->nullable()->after('billing_cycle');
                $table->decimal('annual_price', 10, 2)->nullable()->after('monthly_price');
                $table->string('currency', 3)->default('USD')->after('annual_price');
                $table->json('included_features')->nullable()->after('currency');
                $table->integer('api_quota')->nullable()->after('included_features');
                $table->integer('trade_quota')->nullable()->after('api_quota');
                $table->boolean('auto_renew')->default(true)->after('trade_quota');
                $table->timestamp('last_billing_date')->nullable()->after('auto_renew');
                $table->timestamp('next_billing_date')->nullable()->after('last_billing_date');

                $table->index(['billing_cycle', 'status']);
                $table->index(['next_billing_date']);
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            // For PostgreSQL
            if (config('database.default') === 'pgsql') {
                $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
                return count($indexes) > 0;
            }
            
            // For SQLite and others, assume index doesn't exist to avoid errors
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};
