<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AiLog;
use App\Models\Alert;
use App\Models\Subscription;
use App\Models\Trade;
use App\Models\UsageCounter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Business Metrics Service
 * Provides comprehensive analytics for business intelligence and monitoring
 */
class BusinessMetricsService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get trading performance metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getTradingMetrics(string $period = '24h'): array
    {
        $cacheKey = "trading_metrics_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $trades = Trade::whereBetween('created_at', $dateRange);
            $closedTrades = clone $trades->where('status', 'CLOSED');

            $totalTrades = $trades->count();
            $totalClosed = $closedTrades->count();
            $totalPnl = $closedTrades->sum('pnl_realized') ?? 0;
            $totalVolume = $trades->sum(DB::raw('qty * entry_price'));

            $winningTrades = $closedTrades->where('pnl_realized', '>', 0)->count();
            $losingTrades = $closedTrades->where('pnl_realized', '<', 0)->count();

            $avgWin = $closedTrades->where('pnl_realized', '>', 0)->avg('pnl_realized') ?? 0;
            $avgLoss = abs((float) ($closedTrades->where('pnl_realized', '<', 0)->avg('pnl_realized') ?? 0));

            $winRate = $totalClosed > 0 ? ($winningTrades / $totalClosed) * 100 : 0;
            $profitFactor = $avgLoss > 0 ? $avgWin / $avgLoss : 0;

            return [
                'period' => $period,
                'total_trades' => $totalTrades,
                'closed_trades' => $totalClosed,
                'open_trades' => $totalTrades - $totalClosed,
                'total_pnl' => round((float) $totalPnl, 2),
                'total_volume' => round((float) $totalVolume, 2),
                'win_rate' => round((float) $winRate, 2),
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'avg_win' => round((float) $avgWin, 2),
                'avg_loss' => round((float) $avgLoss, 2),
                'profit_factor' => round($profitFactor, 2),
                'best_trade' => $closedTrades->max('pnl_realized') ?? 0,
                'worst_trade' => $closedTrades->min('pnl_realized') ?? 0,
            ];
        });
    }

    /**
     * Get AI performance metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getAiMetrics(string $period = '24h'): array
    {
        $cacheKey = "ai_metrics_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $aiLogs = AiLog::whereBetween('created_at', $dateRange);

            $totalRequests = $aiLogs->count();
            $avgLatency = $aiLogs->avg('latency_ms') ?? 0;
            $maxLatency = $aiLogs->max('latency_ms') ?? 0;

            $providerStats = $aiLogs
                ->select('provider', DB::raw('COUNT(*) as count'), DB::raw('AVG(latency_ms) as avg_latency'))
                ->groupBy('provider')
                ->get()
                ->keyBy('provider')
                ->toArray();

            $actionDistribution = $aiLogs
                ->select('action', DB::raw('COUNT(*) as count'))
                ->whereNotNull('action')
                ->groupBy('action')
                ->get()
                ->pluck('count', 'action')
                ->toArray();

            $confidenceStats = [
                'avg' => $aiLogs->whereNotNull('confidence')->avg('confidence') ?? 0,
                'min' => $aiLogs->whereNotNull('confidence')->min('confidence') ?? 0,
                'max' => $aiLogs->whereNotNull('confidence')->max('confidence') ?? 100,
            ];

            return [
                'period' => $period,
                'total_requests' => $totalRequests,
                'avg_latency_ms' => round((float) $avgLatency, 2),
                'max_latency_ms' => $maxLatency,
                'p95_latency_ms' => $this->calculatePercentile($aiLogs, 'latency_ms', 95),
                'provider_stats' => $providerStats,
                'action_distribution' => $actionDistribution,
                'confidence_stats' => [
                    'avg' => round((float) $confidenceStats['avg'], 2),
                    'min' => $confidenceStats['min'],
                    'max' => $confidenceStats['max'],
                ],
                'requests_per_hour' => $this->getHourlyDistribution($aiLogs, $period),
            ];
        });
    }

    /**
     * Get system health metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getSystemMetrics(string $period = '24h'): array
    {
        $cacheKey = "system_metrics_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $alerts = Alert::whereBetween('created_at', $dateRange);

            $alertCounts = [
                'total' => $alerts->count(),
                'critical' => $alerts->where('severity', 'critical')->count(),
                'error' => $alerts->where('severity', 'error')->count(),
                'warning' => $alerts->where('severity', 'warning')->count(),
                'info' => $alerts->where('severity', 'info')->count(),
            ];

            $alertTypes = $alerts
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray();

            $systemResources = [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'load_average' => $this->getLoadAverage(),
            ];

            return [
                'period' => $period,
                'alerts' => $alertCounts,
                'alert_types' => $alertTypes,
                'resources' => $systemResources,
                'uptime' => $this->getSystemUptime(),
                'error_rate' => $this->calculateErrorRate($period),
            ];
        });
    }

    /**
     * Get SaaS business metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getSaasMetrics(string $period = '30d'): array
    {
        $cacheKey = "saas_metrics_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $subscriptions = Subscription::all();
            $newSubscriptions = Subscription::whereBetween('created_at', $dateRange);

            $planDistribution = $subscriptions
                ->groupBy('plan')
                ->map(fn ($group) => $group->count())
                ->toArray();

            $churnRate = $this->calculateChurnRate($period);
            $mrr = $this->calculateMRR();
            $arpu = $this->calculateARPU();

            $usageStats = UsageCounter::whereBetween('created_at', $dateRange)
                ->select('service', DB::raw('SUM(count) as total_usage'))
                ->groupBy('service')
                ->get()
                ->pluck('total_usage', 'service')
                ->toArray();

            return [
                'period' => $period,
                'total_subscriptions' => $subscriptions->count(),
                'new_subscriptions' => $newSubscriptions->count(),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
                'plan_distribution' => $planDistribution,
                'churn_rate' => round($churnRate, 2),
                'mrr' => round($mrr, 2),
                'arpu' => round($arpu, 2),
                'usage_stats' => $usageStats,
                'customer_lifetime_value' => round($this->calculateCLV(), 2),
            ];
        });
    }

    /**
     * Get comprehensive dashboard data
     */
    /**
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(string $period = '24h'): array
    {
        return [
            'trading' => $this->getTradingMetrics($period),
            'ai' => $this->getAiMetrics($period),
            'system' => $this->getSystemMetrics($period),
            'saas' => $this->getSaasMetrics('30d'), // SaaS metrics typically monthly
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get real-time performance metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'active_connections' => $this->getActiveConnections(),
            'current_load' => $this->getCurrentLoad(),
            'memory_usage' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => round($this->getMemoryLimit() / 1024 / 1024, 2),
            ],
            'cache_stats' => $this->getCacheStats(),
            'database_connections' => $this->getDatabaseConnections(),
            'queue_sizes' => $this->getQueueSizes(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Private helper methods
     */
    /**
     * @return array<string, mixed>
     */
    private function getDateRange(string $period): array
    {
        $end = Carbon::now();

        $start = match ($period) {
            '1h' => $end->copy()->subHour(),
            '24h' => $end->copy()->subDay(),
            '7d' => $end->copy()->subWeek(),
            '30d' => $end->copy()->subMonth(),
            '90d' => $end->copy()->subMonths(3),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDay(),
        };

        return [$start, $end];
    }

    private function calculatePercentile($query, string $column, int $percentile): float
    {
        $values = $query->pluck($column)->filter()->sort()->values();
        $count = $values->count();

        if ($count === 0) {
            return 0;
        }

        $index = ceil($count * ($percentile / 100)) - 1;

        return $values->get($index, 0);
    }

    /**
     * @param mixed $query
     * @return array<string, mixed>
     */
    private function getHourlyDistribution($query, string $period): array
    {
        $hours = match ($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 24 * 7,
            default => 24,
        };

        return $query
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            return sys_getloadavg();
        }

        return [0, 0, 0]; // Fallback for systems without load average
    }

    private function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('uptime -p');

            return trim($uptime ?: 'Unknown');
        }

        return 'Unknown';
    }

    private function calculateErrorRate(string $period): float
    {
        $dateRange = $this->getDateRange($period);

        $totalAlerts = Alert::whereBetween('created_at', $dateRange)->count();
        $errorAlerts = Alert::whereBetween('created_at', $dateRange)
            ->whereIn('severity', ['error', 'critical'])
            ->count();

        return $totalAlerts > 0 ? ($errorAlerts / $totalAlerts) * 100 : 0;
    }

    private function calculateChurnRate(string $period): float
    {
        $dateRange = $this->getDateRange($period);

        $startingCustomers = Subscription::where('created_at', '<', $dateRange[0])->count();
        $churnedCustomers = Subscription::whereBetween('cancelled_at', $dateRange)->count();

        return $startingCustomers > 0 ? ($churnedCustomers / $startingCustomers) * 100 : 0;
    }

    private function calculateMRR(): float
    {
        return Subscription::where('status', 'active')
            ->where('billing_cycle', 'monthly')
            ->sum('amount') ?? 0;
    }

    private function calculateARPU(): float
    {
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $mrr = $this->calculateMRR();

        return $activeSubscriptions > 0 ? $mrr / $activeSubscriptions : 0;
    }

    private function calculateCLV(): float
    {
        $arpu = $this->calculateARPU();
        $churnRate = $this->calculateChurnRate('30d') / 100;

        return $churnRate > 0 ? $arpu / $churnRate : 0;
    }

    private function getActiveConnections(): int
    {
        try {
            return DB::select('SELECT COUNT(*) as count FROM information_schema.processlist')[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getCurrentLoad(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => (memory_get_usage(true) / $this->getMemoryLimit()) * 100,
            'disk_usage' => $this->getDiskUsage(),
        ];
    }

    private function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();

            return $load ? ($load[0] / 4) * 100 : 0; // Assuming 4 cores
        }

        return 0;
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        return $this->parseMemorySize($limit);
    }

    private function parseMemorySize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function getDiskUsage(): float
    {
        $bytes = disk_total_space('/');
        $free = disk_free_space('/');

        if ($bytes && $free) {
            return (($bytes - $free) / $bytes) * 100;
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCacheStats(): array
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->getRedis();
                $info = $redis->info();

                return [
                    'hit_ratio' => $info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100,
                    'memory_usage' => $info['used_memory_human'] ?? 'Unknown',
                    'connections' => $info['connected_clients'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            // Fallback for other cache drivers
        }

        return [
            'hit_ratio' => 'Unknown',
            'memory_usage' => 'Unknown',
            'connections' => 0,
        ];
    }

    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select('SHOW STATUS WHERE Variable_name = "Threads_connected"');

            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueueSizes(): array
    {
        // This would need to be implemented based on your queue driver
        // For Redis queues:
        try {
            $redis = app('redis');

            return [
                'default' => $redis->llen('queues:default') ?? 0,
                'high' => $redis->llen('queues:high') ?? 0,
                'low' => $redis->llen('queues:low') ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'default' => 0,
                'high' => 0,
                'low' => 0,
            ];
        }
    }
}
