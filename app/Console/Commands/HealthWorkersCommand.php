<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

final class HealthWorkersCommand extends Command
{
    protected $signature = 'sentx:health:workers {queue=default : Queue to check}';

    protected $description = 'Worker health check - queue status, job processing, memory usage';

    public function handle(): int
    {
        $queue = $this->argument('queue');
        $this->info("🔍 Worker Health Check: {$queue}");

        $health = [
            'timestamp' => now()->toISOString(),
            'queue' => $queue,
            'checks' => [],
            'overall' => 'HEALTHY',
        ];

        try {
            // 1. Queue Size Check
            $queueSize = Queue::size($queue);
            $health['checks']['queue_size'] = [
                'status' => $queueSize < 100 ? 'PASS' : ($queueSize < 1000 ? 'WARN' : 'FAIL'),
                'size' => $queueSize,
                'threshold' => [
                    'warning' => 100,
                    'critical' => 1000,
                ],
            ];

            // 2. Failed Jobs Check
            $failedJobs = $this->getFailedJobsCount();
            $health['checks']['failed_jobs'] = [
                'status' => $failedJobs === 0 ? 'PASS' : ($failedJobs < 10 ? 'WARN' : 'FAIL'),
                'count' => $failedJobs,
                'threshold' => [
                    'warning' => 1,
                    'critical' => 10,
                ],
            ];

            // 3. Memory Usage Check
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->getMemoryLimit();
            $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

            $health['checks']['memory_usage'] = [
                'status' => $memoryPercent < 80 ? 'PASS' : ($memoryPercent < 90 ? 'WARN' : 'FAIL'),
                'usage_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimit,
                'usage_percent' => round($memoryPercent, 2),
                'threshold' => [
                    'warning' => 80,
                    'critical' => 90,
                ],
            ];

            // 4. Heartbeat Check
            $heartbeat = Cache::get('worker:heartbeat:'.$queue);
            $heartbeatAge = $heartbeat ? now()->diffInSeconds($heartbeat) : null;

            $health['checks']['heartbeat'] = [
                'status' => $heartbeatAge === null ? 'FAIL' : ($heartbeatAge < 300 ? 'PASS' : 'FAIL'),
                'last_heartbeat' => $heartbeat,
                'age_seconds' => $heartbeatAge,
                'threshold' => [
                    'warning' => 60,
                    'critical' => 300,
                ],
            ];

            // 5. Lock Status Check
            $lockStatus = $this->checkLocks();
            $health['checks']['locks'] = [
                'status' => $lockStatus['healthy'] ? 'PASS' : 'FAIL',
                'active_locks' => $lockStatus['active'],
                'stale_locks' => $lockStatus['stale'],
                'details' => $lockStatus['details'],
            ];

        } catch (\Throwable $e) {
            $health['overall'] = 'UNHEALTHY';
            $health['checks']['error'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];

            Log::error('Worker health check failed', [
                'queue' => $queue,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Overall status determination
        $failedChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'FAIL'
        );

        $warningChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'WARN'
        );

        if (count($failedChecks) > 0) {
            $health['overall'] = 'UNHEALTHY';
        } elseif (count($warningChecks) > 0) {
            $health['overall'] = 'DEGRADED';
        }

        // Output
        if ($this->option('verbose')) {
            $this->table(['Check', 'Status', 'Details'], array_map(function ($name, $check) {
                return [
                    $name,
                    $check['status'] ?? 'UNKNOWN',
                    json_encode($check, JSON_UNESCAPED_UNICODE),
                ];
            }, array_keys($health['checks']), $health['checks']));
        }

        $this->info("Overall Status: {$health['overall']}");

        if ($health['overall'] === 'HEALTHY') {
            $this->info('✅ Workers are healthy');

            return 0;
        } elseif ($health['overall'] === 'DEGRADED') {
            $this->warn('⚠️ Workers are degraded');

            return 1;
        } else {
            $this->error('❌ Workers are unhealthy');

            return 2;
        }
    }

    private function getFailedJobsCount(): int
    {
        try {
            return \DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            return 0; // Table might not exist
        }
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'k' => $value * 1024,
            'm' => $value * 1024 * 1024,
            'g' => $value * 1024 * 1024 * 1024,
            default => $value
        };
    }

    private function checkLocks(): array
    {
        $locks = [
            'lock:open_now',
            'lock:manage:default:BTCUSDT',
            'lock:action:default:BTCUSDT',
        ];

        $active = 0;
        $stale = 0;
        $details = [];

        foreach ($locks as $lockName) {
            $lockValue = Cache::get($lockName);
            if ($lockValue) {
                $active++;
                $details[] = [
                    'name' => $lockName,
                    'value' => $lockValue,
                    'status' => 'ACTIVE',
                ];
            }
        }

        return [
            'healthy' => $stale === 0,
            'active' => $active,
            'stale' => $stale,
            'details' => $details,
        ];
    }
}
