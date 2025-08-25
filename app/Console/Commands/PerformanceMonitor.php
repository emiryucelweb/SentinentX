<?php

namespace App\Console\Commands;

use App\Services\Optimization\CacheOptimizer;
use App\Services\Optimization\QueryOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Performance Monitoring Command
 * Real-time system performance monitoring
 */
class PerformanceMonitor extends Command
{
    protected $signature = 'sentx:performance:monitor {--warm-cache} {--clear-cache} {--stats}';

    protected $description = 'Monitor and optimize system performance';

    public function __construct(
        private CacheOptimizer $cacheOptimizer,
        private QueryOptimizer $queryOptimizer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('SentientX Performance Monitor');
        $this->line('================================');

        // Warm cache if requested
        if ($this->option('warm-cache')) {
            $this->warmCaches();
        }

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->clearCaches();
        }

        // Show stats if requested
        if ($this->option('stats')) {
            $this->showStats();
        }

        // If no options, show current status
        if (! $this->option('warm-cache') && ! $this->option('clear-cache') && ! $this->option('stats')) {
            $this->showCurrentStatus();
        }

        return 0;
    }

    private function warmCaches(): void
    {
        $this->info('🔥 Warming up caches...');

        $start = microtime(true);
        $warmed = $this->cacheOptimizer->warmUpCaches();
        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->table(['Cache', 'Status'], [
            ['Trading Stats', '✅ Warmed'],
            ['Lab Metrics', '✅ Warmed'],
        ]);

        $this->info("✅ Cache warm-up completed in {$duration}ms");
    }

    private function clearCaches(): void
    {
        $this->warn('🗑️  Clearing all caches...');

        $cleared = $this->cacheOptimizer->clearAllCaches();

        if ($cleared) {
            $this->info('✅ All caches cleared successfully');
        } else {
            $this->error('❌ Failed to clear caches');
        }
    }

    private function showStats(): void
    {
        $this->info('📊 Performance Statistics');
        $this->line('========================');

        // Cache stats
        $cacheStats = $this->cacheOptimizer->getCacheStats();
        $this->info("Cache Driver: {$cacheStats['driver']}");

        // Query stats
        $queryStats = $this->queryOptimizer->getQueryStats();
        if (isset($queryStats['total_queries'])) {
            $this->table(['Metric', 'Value'], [
                ['Total Queries', $queryStats['total_queries']],
                ['Total Duration', $queryStats['total_duration_ms'].'ms'],
                ['Average Duration', $queryStats['avg_duration_ms'].'ms'],
                ['Slow Queries', $queryStats['slow_queries']],
                ['Threshold', $queryStats['slow_query_threshold_ms'].'ms'],
            ]);
        }

        // Database stats
        $this->showDatabaseStats();
    }

    private function showCurrentStatus(): void
    {
        $this->info('📈 Current System Status');
        $this->line('========================');

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $this->table(['Metric', 'Value'], [
            ['Memory Usage', $this->formatBytes($memoryUsage)],
            ['Peak Memory', $this->formatBytes($peakMemory)],
            ['Environment', config('app.env')],
            ['Debug Mode', config('app.debug') ? 'ON' : 'OFF'],
            ['Cache Driver', config('cache.default')],
            ['Session Driver', config('session.driver')],
            ['Queue Driver', config('queue.default')],
        ]);

        // Quick trading metrics
        $tradingMetrics = $this->queryOptimizer->getTradingMetrics();

        $this->info('Trading Metrics:');
        $this->table(['Metric', 'Value'], [
            ['Total Trades', $tradingMetrics['total_trades']],
            ['Open Trades', $tradingMetrics['open_trades']],
            ['Closed Trades', $tradingMetrics['closed_trades']],
            ['Profit Factor', $tradingMetrics['profit_factor']],
            ['Avg PnL', '$'.$tradingMetrics['avg_pnl']],
            ['Recent Trades (24h)', $tradingMetrics['recent_trades']],
        ]);

        $this->line('');
        $this->info('Available Options:');
        $this->line('  --warm-cache    Warm up application caches');
        $this->line('  --clear-cache   Clear all application caches');
        $this->line('  --stats         Show detailed performance statistics');
    }

    private function showDatabaseStats(): void
    {
        try {
            // Database connection test
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);

            // Table sizes
            $tableStats = DB::select("
                SELECT 
                    name as table_name,
                    CASE 
                        WHEN sql LIKE '%CREATE TABLE%' THEN 'table'
                        ELSE 'other'
                    END as type
                FROM sqlite_master 
                WHERE type='table' AND name NOT LIKE 'sqlite_%'
                ORDER BY name
            ");

            $this->info('Database Status:');
            $this->table(['Metric', 'Value'], [
                ['Connection', '✅ Connected'],
                ['Latency', $dbLatency.'ms'],
                ['Driver', config('database.default')],
                ['Tables', count($tableStats)],
            ]);

        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: '.$e->getMessage());
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
