<?php

namespace App\Services\Optimization;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Query Optimizer
 * Production-grade database query optimizations
 */
class QueryOptimizer
{
    private array $queryLog = [];

    private float $slowQueryThreshold = 100.0; // 100ms

    /**
     * Execute optimized query with performance monitoring
     */
    public function optimizedQuery(callable $query, string $context = 'unknown'): mixed
    {
        $start = microtime(true);

        // Enable query logging for this request
        DB::enableQueryLog();

        try {
            $result = $query();

            $duration = (microtime(true) - $start) * 1000;
            $queries = DB::getQueryLog();

            // Log slow queries
            if ($duration > $this->slowQueryThreshold) {
                Log::warning('Slow query detected', [
                    'context' => $context,
                    'duration_ms' => round($duration, 2),
                    'query_count' => count($queries),
                    'queries' => $queries,
                ]);
            }

            // Store query stats
            $this->queryLog[] = [
                'context' => $context,
                'duration_ms' => round($duration, 2),
                'query_count' => count($queries),
                'timestamp' => now(),
            ];

            return $result;

        } finally {
            DB::disableQueryLog();
        }
    }

    /**
     * Optimize trades query with proper indexing
     */
    public function getTradesOptimized(array $filters = []): Collection
    {
        return $this->optimizedQuery(function () use ($filters) {
            $query = DB::table('trades')
                ->select([
                    'id', 'symbol', 'side', 'status', 'entry_price', 'quantity',
                    'realized_pnl', 'unrealized_pnl', 'created_at', 'updated_at',
                ]);

            // Apply filters efficiently
            if (! empty($filters['symbol'])) {
                $query->where('symbol', $filters['symbol']);
            }

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            // Use proper ordering with index
            return $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 100)
                ->get();
        }, 'trades_query');
    }

    /**
     * Optimize AI logs query
     */
    public function getAiLogsOptimized(array $filters = []): Collection
    {
        return $this->optimizedQuery(function () use ($filters) {
            $query = DB::table('ai_logs')
                ->select([
                    'id', 'cycle_uuid', 'round', 'stage', 'provider', 'action',
                    'confidence', 'latency_ms', 'created_at',
                ]);

            if (! empty($filters['cycle_uuid'])) {
                $query->where('cycle_uuid', $filters['cycle_uuid']);
            }

            if (! empty($filters['provider'])) {
                $query->where('provider', $filters['provider']);
            }

            return $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 50)
                ->get();
        }, 'ai_logs_query');
    }

    /**
     * Optimize market data query
     */
    public function getMarketDataOptimized(string $symbol, int $limit = 100): Collection
    {
        return $this->optimizedQuery(function () use ($symbol, $limit) {
            return DB::table('market_data')
                ->select(['symbol', 'price', 'volume', 'timestamp', 'created_at'])
                ->where('symbol', $symbol)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->get();
        }, "market_data_{$symbol}");
    }

    /**
     * Batch insert with transaction optimization
     */
    public function batchInsertOptimized(string $table, array $data, int $chunkSize = 1000): bool
    {
        return $this->optimizedQuery(function () use ($table, $data, $chunkSize) {
            return DB::transaction(function () use ($table, $data, $chunkSize) {
                $chunks = array_chunk($data, $chunkSize);

                foreach ($chunks as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                return true;
            });
        }, "batch_insert_{$table}");
    }

    /**
     * Aggregate queries with caching
     */
    public function getTradingMetrics(): array
    {
        return $this->optimizedQuery(function () {
            $metrics = DB::table('trades')
                ->selectRaw('
                    COUNT(*) as total_trades,
                    COUNT(CASE WHEN status = "open" THEN 1 END) as open_trades,
                    COUNT(CASE WHEN status = "closed" THEN 1 END) as closed_trades,
                    SUM(CASE WHEN realized_pnl > 0 THEN realized_pnl ELSE 0 END) as total_profit,
                    SUM(CASE WHEN realized_pnl < 0 THEN ABS(realized_pnl) ELSE 0 END) as total_loss,
                    AVG(realized_pnl) as avg_pnl,
                    COUNT(CASE WHEN created_at >= datetime("now", "-1 day") THEN 1 END) as recent_trades
                ')
                ->first();

            return [
                'total_trades' => $metrics->total_trades ?? 0,
                'open_trades' => $metrics->open_trades ?? 0,
                'closed_trades' => $metrics->closed_trades ?? 0,
                'total_profit' => $metrics->total_profit ?? 0,
                'total_loss' => $metrics->total_loss ?? 0,
                'avg_pnl' => round($metrics->avg_pnl ?? 0, 2),
                'recent_trades' => $metrics->recent_trades ?? 0,
                'profit_factor' => $metrics->total_loss > 0
                    ? round($metrics->total_profit / $metrics->total_loss, 2)
                    : 0,
            ];
        }, 'trading_metrics');
    }

    /**
     * Get query performance statistics
     */
    public function getQueryStats(): array
    {
        if (empty($this->queryLog)) {
            return ['message' => 'No queries logged'];
        }

        $totalQueries = count($this->queryLog);
        $totalDuration = array_sum(array_column($this->queryLog, 'duration_ms'));
        $avgDuration = $totalDuration / $totalQueries;

        $slowQueries = array_filter($this->queryLog, function ($log) {
            return $log['duration_ms'] > $this->slowQueryThreshold;
        });

        return [
            'total_queries' => $totalQueries,
            'total_duration_ms' => round($totalDuration, 2),
            'avg_duration_ms' => round($avgDuration, 2),
            'slow_queries' => count($slowQueries),
            'slow_query_threshold_ms' => $this->slowQueryThreshold,
            'recent_queries' => array_slice($this->queryLog, -10),
        ];
    }

    /**
     * Reset query statistics
     */
    public function resetStats(): void
    {
        $this->queryLog = [];
    }
}
