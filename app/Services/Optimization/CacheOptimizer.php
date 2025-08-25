<?php

namespace App\Services\Optimization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cache Optimizer Service
 * Production-grade caching optimizations
 */
class CacheOptimizer
{
    private const CACHE_TTL = 3600; // 1 hour default

    private const LONG_CACHE_TTL = 86400; // 24 hours for static data

    private const SHORT_CACHE_TTL = 300; // 5 minutes for dynamic data

    /**
     * Optimize database queries with intelligent caching
     */
    public function optimizeQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL;

        return Cache::remember($key, $ttl, function () use ($callback, $key) {
            $start = microtime(true);
            $result = $callback();
            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::info('Query optimized', [
                'cache_key' => $key,
                'execution_time_ms' => $duration,
                'cached' => true,
            ]);

            return $result;
        });
    }

    /**
     * Cache market data with short TTL
     */
    public function cacheMarketData(string $symbol, callable $callback): mixed
    {
        return $this->optimizeQuery(
            "market_data:{$symbol}",
            $callback,
            self::SHORT_CACHE_TTL
        );
    }

    /**
     * Cache instrument info with long TTL
     */
    public function cacheInstrumentInfo(string $symbol, callable $callback): mixed
    {
        return $this->optimizeQuery(
            "instrument_info:{$symbol}",
            $callback,
            self::LONG_CACHE_TTL
        );
    }

    /**
     * Cache AI consensus with medium TTL
     */
    public function cacheAiConsensus(string $hash, callable $callback): mixed
    {
        return $this->optimizeQuery(
            "ai_consensus:{$hash}",
            $callback,
            self::CACHE_TTL
        );
    }

    /**
     * Cache trading statistics
     */
    public function cacheTradingStats(callable $callback): mixed
    {
        return $this->optimizeQuery(
            'trading_stats',
            $callback,
            self::SHORT_CACHE_TTL
        );
    }

    /**
     * Cache lab metrics
     */
    public function cacheLabMetrics(callable $callback): mixed
    {
        return $this->optimizeQuery(
            'lab_metrics',
            $callback,
            self::SHORT_CACHE_TTL
        );
    }

    /**
     * Warm up critical caches
     */
    public function warmUpCaches(): array
    {
        $warmed = [];

        // Warm up trading stats
        $warmed['trading_stats'] = $this->cacheTradingStats(function () {
            return DB::table('trades')->selectRaw('
                COUNT(*) as total_trades,
                COUNT(CASE WHEN status = "open" THEN 1 END) as open_trades,
                COUNT(CASE WHEN created_at >= NOW() - INTERVAL 1 DAY THEN 1 END) as recent_trades
            ')->first();
        });

        // Warm up lab metrics
        $warmed['lab_metrics'] = $this->cacheLabMetrics(function () {
            return DB::table('lab_runs')->selectRaw('
                COUNT(*) as total_runs,
                COUNT(CASE WHEN created_at >= NOW() - INTERVAL 1 DAY THEN 1 END) as recent_runs
            ')->first();
        });

        Log::info('Caches warmed up', ['warmed_caches' => array_keys($warmed)]);

        return $warmed;
    }

    /**
     * Clear all application caches
     */
    public function clearAllCaches(): bool
    {
        try {
            Cache::flush();
            Log::info('All caches cleared successfully');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear caches', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'store' => Cache::getStore(),
        ];

        if (method_exists(Cache::getStore(), 'getRedis')) {
            $redis = Cache::getStore()->getRedis();
            $info = $redis->info();
            $stats['redis'] = [
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'unknown',
            ];
        }

        return $stats;
    }
}
