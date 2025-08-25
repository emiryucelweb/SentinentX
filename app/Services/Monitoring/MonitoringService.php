<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Exchange\BybitClient;
use App\Services\Risk\DriftGuardService;
use Illuminate\Support\Facades\Log;

final class MonitoringService
{
    public function __construct(
        private BybitClient $bybitClient,
        private DriftGuardService $driftGuard
    ) {}

    public function checkSystemHealth(): array
    {
        $health = [
            'timestamp' => now()->toISOString(),
            'status' => 'healthy',
            'checks' => [],
        ];

        // Exchange connectivity
        $health['checks']['exchange'] = $this->checkExchangeHealth();

        // Risk monitoring
        $health['checks']['risk'] = $this->checkRiskHealth();

        // System resources
        $health['checks']['resources'] = $this->checkResourceHealth();

        // Database connectivity
        $health['checks']['database'] = $this->checkDatabaseHealth();

        // Overall status
        $failedChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'failed'
        );

        if (! empty($failedChecks)) {
            $health['status'] = 'unhealthy';
        }

        return $health;
    }

    private function checkExchangeHealth(): array
    {
        try {
            $serverTime = $this->bybitClient->getServerTime();

            return [
                'status' => 'healthy',
                'server_time' => $serverTime,
                'latency' => $this->measureLatency(),
            ];
        } catch (\Exception $e) {
            Log::error('Exchange health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkRiskHealth(): array
    {
        try {
            $driftStatus = $this->driftGuard->getCurrentStatus();

            return [
                'status' => 'healthy',
                'drift_guard' => $driftStatus,
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkResourceHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');

        return [
            'status' => 'healthy',
            'memory_usage' => $this->formatBytes($memoryUsage),
            'memory_limit' => $memoryLimit,
            'cpu_load' => sys_getloadavg(),
        ];
    }

    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();

            return [
                'status' => 'healthy',
                'connection' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function measureLatency(): float
    {
        $start = microtime(true);
        $this->bybitClient->getServerTime();

        return (microtime(true) - $start) * 1000; // ms
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }
}
