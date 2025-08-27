<?php

declare(strict_types=1);

namespace App\Services\Observability;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * MetricsCollector - Business and operational metrics
 * Stores metrics in Redis with time-series structure for monitoring dashboards
 */
final class MetricsCollector
{
    private const METRICS_PREFIX = 'metrics:';

    private const TTL_HOUR = 3600;

    private const TTL_DAY = 86400;

    private const TTL_WEEK = 604800;

    /**
     * Increment a counter metric
     */
    public function incrementCounter(string $metric, array $tags = [], int $value = 1): void
    {
        $key = $this->buildMetricKey($metric, $tags);
        $timestamp = now()->timestamp;

        // Store hourly, daily, and weekly aggregations
        $this->storeTimeSeriesMetric($key, $timestamp, $value, 'counter');

        Log::channel('structured')->info('METRIC_COUNTER', [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp,
            'category' => 'metrics',
        ]);
    }

    /**
     * Record a gauge metric (current value)
     */
    public function recordGauge(string $metric, float $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($metric, $tags);
        $timestamp = now()->timestamp;

        $this->storeTimeSeriesMetric($key, $timestamp, $value, 'gauge');

        Log::channel('structured')->info('METRIC_GAUGE', [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp,
            'category' => 'metrics',
        ]);
    }

    /**
     * Record a histogram metric (duration, size, etc.)
     */
    public function recordHistogram(string $metric, float $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($metric, $tags);
        $timestamp = now()->timestamp;

        $this->storeTimeSeriesMetric($key, $timestamp, $value, 'histogram');

        Log::channel('structured')->info('METRIC_HISTOGRAM', [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp,
            'category' => 'metrics',
        ]);
    }

    /**
     * Trading-specific metrics
     */
    public function tradingMetrics(string $symbol, string $action, array $data = []): void
    {
        $tags = ['symbol' => $symbol, 'action' => $action];

        // Increment trade counter
        $this->incrementCounter('trading.actions', $tags);

        // Record PnL if available
        if (isset($data['pnl_pct'])) {
            $this->recordHistogram('trading.pnl_percentage', (float) $data['pnl_pct'], $tags);
        }

        // Record position size if available
        if (isset($data['position_size_usd'])) {
            $this->recordHistogram('trading.position_size_usd', (float) $data['position_size_usd'], $tags);
        }

        // Record execution time if available
        if (isset($data['execution_time_ms'])) {
            $this->recordHistogram('trading.execution_time_ms', (float) $data['execution_time_ms'], $tags);
        }
    }

    /**
     * AI Consensus metrics
     */
    public function aiConsensusMetrics(string $provider, string $decision, int $confidence, array $data = []): void
    {
        $tags = ['provider' => $provider, 'decision' => $decision];

        // Increment consensus counter
        $this->incrementCounter('ai.consensus.decisions', $tags);

        // Record confidence level
        $this->recordGauge('ai.consensus.confidence', $confidence, $tags);

        // Record response time if available
        if (isset($data['response_time_ms'])) {
            $this->recordHistogram('ai.response_time_ms', (float) $data['response_time_ms'], $tags);
        }
    }

    /**
     * Risk metrics
     */
    public function riskMetrics(string $gate, bool $passed, array $data = []): void
    {
        $tags = ['gate' => $gate, 'result' => $passed ? 'pass' : 'fail'];

        // Increment risk gate counter
        $this->incrementCounter('risk.gates', $tags);

        // Record risk score if available
        if (isset($data['risk_score'])) {
            $this->recordGauge('risk.score', (float) $data['risk_score'], ['gate' => $gate]);
        }
    }

    /**
     * System performance metrics
     */
    public function performanceMetrics(string $operation, float $durationMs, array $data = []): void
    {
        $tags = ['operation' => $operation];

        // Record operation duration
        $this->recordHistogram('performance.duration_ms', $durationMs, $tags);

        // Record memory usage if available
        if (isset($data['memory_mb'])) {
            $this->recordGauge('performance.memory_mb', (float) $data['memory_mb'], $tags);
        }

        // Record CPU usage if available
        if (isset($data['cpu_percent'])) {
            $this->recordGauge('performance.cpu_percent', (float) $data['cpu_percent'], $tags);
        }
    }

    /**
     * Business metrics (SaaS)
     */
    public function businessMetrics(string $metric, float $value, array $tags = []): void
    {
        $this->recordGauge("business.{$metric}", $value, $tags);

        // Special handling for key business metrics
        if ($metric === 'active_users') {
            $this->incrementCounter('business.user_activity', $tags, (int) $value);
        }

        if ($metric === 'revenue_usd') {
            $this->recordHistogram('business.revenue_per_transaction', $value, $tags);
        }
    }

    /**
     * Get metrics for a specific period
     */
    public function getMetrics(string $metric, array $tags = [], string $period = 'hour', int $count = 24): array
    {
        $key = $this->buildMetricKey($metric, $tags);
        $data = [];

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $this->getPeriodTimestamp($period, $i);
            $periodKey = "{$key}:{$period}:{$timestamp}";
            $value = Redis::hgetall($periodKey);

            if (! empty($value)) {
                $data[] = [
                    'timestamp' => $timestamp,
                    'value' => $value,
                ];
            }
        }

        return array_reverse($data); // Most recent first
    }

    /**
     * Get system health metrics summary
     */
    public function getHealthMetrics(): array
    {
        $lastHour = now()->subHour()->timestamp;

        return [
            'trading' => [
                'total_actions' => $this->getCounterValue('trading.actions', [], $lastHour),
                'avg_pnl_pct' => $this->getAverageValue('trading.pnl_percentage', [], $lastHour),
                'total_volume_usd' => $this->getSumValue('trading.position_size_usd', [], $lastHour),
            ],
            'ai' => [
                'total_decisions' => $this->getCounterValue('ai.consensus.decisions', [], $lastHour),
                'avg_confidence' => $this->getAverageValue('ai.consensus.confidence', [], $lastHour),
                'avg_response_time_ms' => $this->getAverageValue('ai.response_time_ms', [], $lastHour),
            ],
            'risk' => [
                'total_gates' => $this->getCounterValue('risk.gates', [], $lastHour),
                'pass_rate' => $this->getRiskPassRate($lastHour),
            ],
            'performance' => [
                'avg_duration_ms' => $this->getAverageValue('performance.duration_ms', [], $lastHour),
                'max_memory_mb' => $this->getMaxValue('performance.memory_mb', [], $lastHour),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Build metric key with tags
     */
    private function buildMetricKey(string $metric, array $tags): string
    {
        $tagString = '';
        if (! empty($tags)) {
            ksort($tags);
            $tagPairs = [];
            foreach ($tags as $key => $value) {
                $tagPairs[] = "{$key}={$value}";
            }
            $tagString = '|'.implode('|', $tagPairs);
        }

        return self::METRICS_PREFIX.$metric.$tagString;
    }

    /**
     * Store time-series metric data
     */
    private function storeTimeSeriesMetric(string $key, int $timestamp, float $value, string $type): void
    {
        $periods = [
            'minute' => ['interval' => 60, 'ttl' => self::TTL_HOUR],
            'hour' => ['interval' => 3600, 'ttl' => self::TTL_DAY],
            'day' => ['interval' => 86400, 'ttl' => self::TTL_WEEK],
        ];

        foreach ($periods as $period => $config) {
            $periodTimestamp = floor($timestamp / $config['interval']) * $config['interval'];
            $periodKey = "{$key}:{$period}:{$periodTimestamp}";

            // Store different aggregations based on type
            switch ($type) {
                case 'counter':
                    Redis::hincrby($periodKey, 'count', (int) $value);
                    break;
                case 'gauge':
                    Redis::hset($periodKey, 'value', $value);
                    Redis::hset($periodKey, 'timestamp', $timestamp);
                    break;
                case 'histogram':
                    Redis::hincrby($periodKey, 'count', 1);
                    Redis::hincrbyfloat($periodKey, 'sum', $value);
                    $this->updateMinMax($periodKey, $value);
                    break;
            }

            Redis::expire($periodKey, $config['ttl']);
        }
    }

    /**
     * Update min/max values for histogram
     */
    private function updateMinMax(string $key, float $value): void
    {
        $current = Redis::hmget($key, ['min', 'max']);

        $min = $current[0] !== null ? min((float) $current[0], $value) : $value;
        $max = $current[1] !== null ? max((float) $current[1], $value) : $value;

        Redis::hset($key, 'min', $min);
        Redis::hset($key, 'max', $max);
    }

    /**
     * Get period timestamp for historical data
     */
    private function getPeriodTimestamp(string $period, int $offset): int
    {
        $interval = match ($period) {
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 3600,
        };

        $now = now()->timestamp;

        return floor(($now - ($offset * $interval)) / $interval) * $interval;
    }

    /**
     * Helper methods for metric aggregations
     */
    private function getCounterValue(string $metric, array $tags, int $fromTimestamp): int
    {
        $key = $this->buildMetricKey($metric, $tags);
        $hourTimestamp = floor($fromTimestamp / 3600) * 3600;
        $periodKey = "{$key}:hour:{$hourTimestamp}";

        return (int) Redis::hget($periodKey, 'count') ?: 0;
    }

    private function getAverageValue(string $metric, array $tags, int $fromTimestamp): float
    {
        $key = $this->buildMetricKey($metric, $tags);
        $hourTimestamp = floor($fromTimestamp / 3600) * 3600;
        $periodKey = "{$key}:hour:{$hourTimestamp}";

        $sum = (float) Redis::hget($periodKey, 'sum') ?: 0;
        $count = (int) Redis::hget($periodKey, 'count') ?: 0;

        return $count > 0 ? $sum / $count : 0;
    }

    private function getSumValue(string $metric, array $tags, int $fromTimestamp): float
    {
        $key = $this->buildMetricKey($metric, $tags);
        $hourTimestamp = floor($fromTimestamp / 3600) * 3600;
        $periodKey = "{$key}:hour:{$hourTimestamp}";

        return (float) Redis::hget($periodKey, 'sum') ?: 0;
    }

    private function getMaxValue(string $metric, array $tags, int $fromTimestamp): float
    {
        $key = $this->buildMetricKey($metric, $tags);
        $hourTimestamp = floor($fromTimestamp / 3600) * 3600;
        $periodKey = "{$key}:hour:{$hourTimestamp}";

        return (float) Redis::hget($periodKey, 'max') ?: 0;
    }

    private function getRiskPassRate(int $fromTimestamp): float
    {
        $totalPasses = $this->getCounterValue('risk.gates', ['result' => 'pass'], $fromTimestamp);
        $totalFails = $this->getCounterValue('risk.gates', ['result' => 'fail'], $fromTimestamp);
        $total = $totalPasses + $totalFails;

        return $total > 0 ? ($totalPasses / $total) * 100 : 100;
    }
}
