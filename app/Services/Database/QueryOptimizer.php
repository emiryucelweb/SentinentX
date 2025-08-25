<?php

declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Database Query Optimizer for SaaS Trading Platform
 * Focuses on performance-critical trading queries
 */
class QueryOptimizer
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Optimized query for active trades with eager loading
     */
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function getActiveTrades(?string $tenant = null): \Illuminate\Database\Query\Builder
    {
        return DB::table('trades')
            ->select([
                'id', 'symbol', 'side', 'qty', 'entry_price',
                'stop_loss', 'take_profit', 'status', 'tenant_id',
                'created_at', 'updated_at',
            ])
            ->where('status', 'OPEN')
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Cached aggregated PnL calculation
     */
    public function getTotalPnl(?string $tenant = null): float
    {
        $cacheKey = 'total_pnl:'.($tenant ?? 'default');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return (float) DB::table('trades')
                ->where('status', 'CLOSED')
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant))
                ->sum('pnl_realized') ?? 0.0;
        });
    }

    /**
     * Performance metrics with database-level aggregation
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(?string $tenant = null, int $days = 30): array
    {
        $cacheKey = "perf_metrics:{$days}:".($tenant ?? 'default');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant, $days) {
            $cutoff = now()->subDays($days);

            $baseQuery = DB::table('trades')
                ->where('status', 'CLOSED')
                ->where('created_at', '>=', $cutoff)
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant));

            $results = $baseQuery->selectRaw('
                COUNT(*) as total_trades,
                COUNT(CASE WHEN pnl_realized > 0 THEN 1 END) as winning_trades,
                COUNT(CASE WHEN pnl_realized < 0 THEN 1 END) as losing_trades,
                SUM(pnl_realized) as total_pnl,
                AVG(pnl_realized) as avg_pnl,
                MAX(pnl_realized) as max_win,
                MIN(pnl_realized) as max_loss,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_duration_minutes
            ')->first();

            if (! $results || ($results->total_trades ?? 0) == 0) {
                return $this->getEmptyMetrics();
            }

            $winRate = ($results->winning_trades ?? 0) / ($results->total_trades ?? 1);
            $avgWin = ($results->winning_trades ?? 0) > 0 ?
                $baseQuery->where('pnl_realized', '>', 0)->avg('pnl_realized') : 0;
            $avgLoss = ($results->losing_trades ?? 0) > 0 ?
                abs((float) ($baseQuery->where('pnl_realized', '<', 0)->avg('pnl_realized') ?? 0)) : 0;

            $profitFactor = ($avgLoss > 0) ? ($avgWin * $winRate) / ($avgLoss * (1 - $winRate)) : 0;

            return [
                'total_trades' => (int) ($results->total_trades ?? 0),
                'winning_trades' => (int) ($results->winning_trades ?? 0),
                'losing_trades' => (int) ($results->losing_trades ?? 0),
                'win_rate' => round($winRate * 100, 2),
                'total_pnl' => round((float) ($results->total_pnl ?? 0), 2),
                'avg_pnl' => round((float) ($results->avg_pnl ?? 0), 2),
                'max_win' => round((float) ($results->max_win ?? 0), 2),
                'max_loss' => round((float) ($results->max_loss ?? 0), 2),
                'avg_win' => round((float) $avgWin, 2),
                'avg_loss' => round((float) $avgLoss, 2),
                'profit_factor' => round((float) $profitFactor, 3),
                'avg_duration_hours' => round((float) ($results->avg_duration_minutes ?? 0) / 60, 2),
                'calculated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get risk exposure by symbol (optimized for dashboard)
     */
    public function getRiskExposure(?string $tenant = null): array
    {
        $cacheKey = 'risk_exposure:'.($tenant ?? 'default');

        return Cache::remember($cacheKey, 60, function () use ($tenant) { // 1 minute cache
            return DB::table('trades')
                ->select([
                    'symbol',
                    DB::raw('SUM(CASE WHEN side = "LONG" THEN qty ELSE -qty END) as net_position'),
                    DB::raw('SUM(ABS(qty * entry_price)) as gross_exposure'),
                    DB::raw('COUNT(*) as open_count'),
                    DB::raw('AVG(entry_price) as avg_entry'),
                ])
                ->where('status', 'OPEN')
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant))
                ->groupBy('symbol')
                ->having('open_count', '>', 0)
                ->orderBy('gross_exposure', 'desc')
                ->get()
                ->map(function ($row) {
                    return [
                        'symbol' => $row->symbol,
                        'net_position' => round((float) $row->net_position, 6),
                        'gross_exposure' => round((float) $row->gross_exposure, 2),
                        'open_count' => (int) $row->open_count,
                        'avg_entry' => round((float) $row->avg_entry, 2),
                        'direction' => $row->net_position > 0 ? 'LONG' : 'SHORT',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Efficient AI decision history with pagination
     */
    public function getRecentAiDecisions(?string $tenant = null, int $limit = 50): array
    {
        return DB::table('ai_logs')
            ->select([
                'id', 'decision_id', 'symbol', 'action', 'confidence',
                'provider', 'created_at',
            ])
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Database health and optimization suggestions
     */
    public function getDatabaseHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'suggestions' => [],
        ];

        // Check for missing indexes on frequently queried columns
        $slowQueries = $this->checkSlowQueries();
        if (count($slowQueries) > 5) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Multiple slow queries detected';
            $health['suggestions'][] = 'Consider adding database indexes';
        }

        // Check table sizes for potential partitioning
        $largeTables = $this->getLargeTableSizes();
        foreach ($largeTables as $table => $sizeKB) {
            if ($sizeKB > 100000) { // 100MB
                $health['suggestions'][] = "Consider partitioning large table: {$table}";
            }
        }

        return $health;
    }

    /**
     * Clear performance caches (for fresh calculations)
     */
    public function clearPerformanceCache(?string $tenant = null): void
    {
        $tenant = $tenant ?? 'default';
        $patterns = [
            "total_pnl:{$tenant}",
            "perf_metrics:*:{$tenant}",
            "risk_exposure:{$tenant}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    private function getEmptyMetrics(): array
    {
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0.0,
            'total_pnl' => 0.0,
            'avg_pnl' => 0.0,
            'max_win' => 0.0,
            'max_loss' => 0.0,
            'avg_win' => 0.0,
            'avg_loss' => 0.0,
            'profit_factor' => 0.0,
            'avg_duration_hours' => 0.0,
            'calculated_at' => now()->toISOString(),
        ];
    }

    private function checkSlowQueries(): array
    {
        try {
            // This would need to be adapted based on the specific database
            return DB::select('SHOW PROCESSLIST') ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getLargeTableSizes(): array
    {
        try {
            $results = DB::select('
                SELECT table_name, ROUND((data_length + index_length) / 1024) as size_kb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY size_kb DESC
                LIMIT 10
            ');

            $sizes = [];
            foreach ($results as $result) {
                $sizes[$result->table_name] = $result->size_kb;
            }

            return $sizes;
        } catch (\Exception $e) {
            return [];
        }
    }
}
