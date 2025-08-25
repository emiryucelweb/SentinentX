<?php

namespace App\Services\SaaS;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-Tenant Management Service
 * Handles tenant isolation, schema management, and data access
 */
class TenantManager
{
    private string $currentTenant = 'default';

    private array $tenantCache = [];

    /**
     * Set current tenant context
     */
    public function setTenant(string $tenantId): void
    {
        $this->currentTenant = $tenantId;

        // Set tenant-specific search path for PostgreSQL
        if (config('database.default') === 'pgsql_saas') {
            DB::statement("SET search_path TO tenant_{$tenantId}, public");
        }

        Log::info('Tenant context set', ['tenant_id' => $tenantId]);
    }

    /**
     * Get current tenant
     */
    public function getCurrentTenant(): string
    {
        return $this->currentTenant;
    }

    /**
     * Create new tenant with isolated schema
     */
    public function createTenant(array $tenantData): array
    {
        $tenantId = $tenantData['tenant_id'];

        DB::transaction(function () use ($tenantId, $tenantData) {
            // Create tenant record
            DB::table('tenants')->insert([
                'tenant_id' => $tenantId,
                'name' => $tenantData['name'],
                'plan' => $tenantData['plan'] ?? 'starter',
                'status' => 'active',
                'settings' => json_encode($tenantData['settings'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create tenant-specific schema
            $this->createTenantSchema($tenantId);

            Log::info('Tenant created successfully', [
                'tenant_id' => $tenantId,
                'name' => $tenantData['name'],
            ]);
        });

        return ['tenant_id' => $tenantId, 'status' => 'created'];
    }

    /**
     * Create tenant-specific database schema
     */
    private function createTenantSchema(string $tenantId): void
    {
        $schemaName = "tenant_{$tenantId}";

        // Create PostgreSQL schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");

        // Set search path to new schema
        DB::statement("SET search_path TO {$schemaName}, public");

        // Create tenant-specific tables
        $this->createTenantTables($tenantId);

        Log::info('Tenant schema created', ['schema' => $schemaName]);
    }

    /**
     * Create tenant-specific tables
     */
    private function createTenantTables(string $tenantId): void
    {
        // Trades table (tenant-specific)
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('trade_id')->unique();
            $table->string('symbol');
            $table->enum('side', ['Buy', 'Sell']);
            $table->enum('status', ['open', 'closed', 'cancelled']);
            $table->decimal('entry_price', 15, 8);
            $table->decimal('exit_price', 15, 8)->nullable();
            $table->decimal('quantity', 15, 8);
            $table->decimal('realized_pnl', 15, 8)->default(0);
            $table->decimal('unrealized_pnl', 15, 8)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['symbol', 'status']);
            $table->index(['created_at']);
            $table->index(['status', 'created_at']);
        });

        // AI Logs table (tenant-specific)
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('cycle_uuid');
            $table->integer('round');
            $table->enum('stage', ['stage1', 'stage2', 'consensus']);
            $table->string('provider');
            $table->enum('action', ['LONG', 'SHORT', 'HOLD', 'NONE']);
            $table->integer('confidence');
            $table->integer('latency_ms');
            $table->json('response_data');
            $table->timestamps();

            // Performance indexes
            $table->index(['cycle_uuid', 'round']);
            $table->index(['provider', 'created_at']);
        });

        // Consensus Decisions table (tenant-specific)
        Schema::create('consensus_decisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('cycle_uuid');
            $table->json('snapshot_data');
            $table->enum('final_action', ['LONG', 'SHORT', 'HOLD', 'NONE']);
            $table->integer('final_confidence');
            $table->json('consensus_meta');
            $table->timestamps();

            $table->index(['cycle_uuid']);
            $table->index(['final_action', 'created_at']);
        });

        // Lab Runs table (tenant-specific)
        Schema::create('lab_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_id')->unique();
            $table->json('config');
            $table->enum('status', ['running', 'completed', 'failed']);
            $table->decimal('net_pnl', 15, 8)->default(0);
            $table->decimal('gross_pnl', 15, 8)->default(0);
            $table->integer('total_trades')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Log::info('Tenant tables created', ['tenant_id' => $tenantId]);
    }

    /**
     * Get tenant configuration
     */
    public function getTenantConfig(string $tenantId): array
    {
        if (isset($this->tenantCache[$tenantId])) {
            return $this->tenantCache[$tenantId];
        }

        $tenant = DB::table('tenants')
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $tenant) {
            throw new \InvalidArgumentException("Tenant not found: {$tenantId}");
        }

        $config = [
            'tenant_id' => $tenant->tenant_id,
            'name' => $tenant->name,
            'plan' => $tenant->plan,
            'status' => $tenant->status,
            'settings' => json_decode($tenant->settings, true),
            'limits' => $this->getTenantLimits($tenant->plan),
        ];

        // Cache for 5 minutes
        $this->tenantCache[$tenantId] = $config;
        Cache::put("tenant_config:{$tenantId}", $config, 300);

        return $config;
    }

    /**
     * Get tenant plan limits
     */
    private function getTenantLimits(string $plan): array
    {
        $limits = [
            'starter' => [
                'max_trades_per_day' => 100,
                'max_ai_requests_per_hour' => 500,
                'max_lab_runs_per_month' => 10,
                'data_retention_days' => 30,
            ],
            'professional' => [
                'max_trades_per_day' => 1000,
                'max_ai_requests_per_hour' => 2000,
                'max_lab_runs_per_month' => 100,
                'data_retention_days' => 90,
            ],
            'enterprise' => [
                'max_trades_per_day' => 10000,
                'max_ai_requests_per_hour' => 10000,
                'max_lab_runs_per_month' => 1000,
                'data_retention_days' => 365,
            ],
            'unlimited' => [
                'max_trades_per_day' => -1,  // Unlimited
                'max_ai_requests_per_hour' => -1,
                'max_lab_runs_per_month' => -1,
                'data_retention_days' => -1,
            ],
        ];

        return $limits[$plan] ?? $limits['starter'];
    }

    /**
     * Check tenant usage limits
     */
    public function checkLimits(string $tenantId, string $resource): bool
    {
        $config = $this->getTenantConfig($tenantId);
        $limits = $config['limits'];

        switch ($resource) {
            case 'trades':
                if ($limits['max_trades_per_day'] === -1) {
                    return true;
                }

                $todayTrades = DB::table('trades')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->count();

                return $todayTrades < $limits['max_trades_per_day'];

            case 'ai_requests':
                if ($limits['max_ai_requests_per_hour'] === -1) {
                    return true;
                }

                $hourlyRequests = DB::table('ai_logs')
                    ->where('created_at', '>=', now()->subHour())
                    ->count();

                return $hourlyRequests < $limits['max_ai_requests_per_hour'];

            default:
                return true;
        }
    }

    /**
     * Get tenant statistics
     */
    public function getTenantStats(string $tenantId): array
    {
        $this->setTenant($tenantId);

        return [
            'total_trades' => DB::table('trades')->count(),
            'open_trades' => DB::table('trades')->where('status', 'open')->count(),
            'total_pnl' => DB::table('trades')->sum('realized_pnl'),
            'ai_requests_today' => DB::table('ai_logs')
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'lab_runs_this_month' => DB::table('lab_runs')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * Delete tenant and all data
     */
    public function deleteTenant(string $tenantId): bool
    {
        DB::transaction(function () use ($tenantId) {
            // Drop tenant schema
            DB::statement("DROP SCHEMA IF EXISTS tenant_{$tenantId} CASCADE");

            // Delete tenant record
            DB::table('tenants')->where('tenant_id', $tenantId)->delete();

            // Clear cache
            unset($this->tenantCache[$tenantId]);
            Cache::forget("tenant_config:{$tenantId}");

            Log::warning('Tenant deleted', ['tenant_id' => $tenantId]);
        });

        return true;
    }
}
