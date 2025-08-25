<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Health\StablecoinHealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    protected $signature = 'sentx:health-check
                          {--json : Output in JSON format}
                          {--timeout=30 : Timeout for external service checks}';

    protected $description = 'Perform comprehensive health checks on all system components';

    public function __construct(
        private StablecoinHealthCheck $stablecoinCheck
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $results = $this->performHealthChecks($timeout);

        if ($this->option('json')) {
            $json = json_encode($results, JSON_PRETTY_PRINT);
            $this->line($json !== false ? $json : 'JSON encoding failed');
        } else {
            $this->displayResults($results);
        }

        // Return non-zero if any critical checks failed
        /** @var array<string, mixed> $checks */
        $checks = $results['checks'] ?? [];
        $hasFailures = collect($checks)->contains('status', 'error');

        return $hasFailures ? 1 : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function performHealthChecks(int $timeout): array
    {
        $checks = [];
        $overallStatus = 'healthy';

        // Database check
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] === 'error') {
            $overallStatus = 'unhealthy';
        }

        // Cache check
        $checks['cache'] = $this->checkCache();
        if ($checks['cache']['status'] === 'error') {
            $overallStatus = 'unhealthy';
        }

        // Stablecoin health check
        $checks['stablecoins'] = $this->checkStablecoins();
        if ($checks['stablecoins']['status'] === 'error') {
            $overallStatus = 'degraded'; // Not critical but concerning
        }

        // External services
        $checks['bybit_api'] = $this->checkBybitAPI($timeout);
        if ($checks['bybit_api']['status'] === 'error') {
            $overallStatus = 'degraded';
        }

        // Storage check
        $checks['storage'] = $this->checkStorage();
        if ($checks['storage']['status'] === 'error') {
            $overallStatus = 'unhealthy';
        }

        return [
            'timestamp' => now()->toISOString(),
            'overall_status' => $overallStatus,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Simple query test
            $count = DB::table('migrations')->count();

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'driver' => DB::connection()->getDriverName(),
                'migrations_count' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_'.uniqid();
            $testValue = 'test_value_'.time();

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved !== $testValue) {
                return [
                    'status' => 'error',
                    'error' => 'Cache write/read mismatch',
                ];
            }

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkStablecoins(): array
    {
        try {
            $results = $this->stablecoinCheck->check();

            $status = 'healthy';
            if ($results['status'] !== 'healthy') {
                $status = $results['status'] === 'depegged' ? 'error' : 'healthy';
            }

            return [
                'status' => $status,
                'overall_status' => $results['status'],
                'stablecoins' => $results['stablecoins'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkBybitAPI(int $timeout): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout($timeout)
                ->get('https://api-testnet.bybit.com/v5/market/time');

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'server_time' => $data['result']['timeSecond'] ?? 'unknown',
                ];
            } else {
                return [
                    'status' => 'error',
                    'http_status' => $response->status(),
                    'response_time_ms' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('logs/health_check_'.uniqid().'.tmp');
            $testContent = 'health_check_'.time();

            file_put_contents($testFile, $testContent);
            $readContent = file_get_contents($testFile);
            unlink($testFile);

            if ($readContent !== $testContent) {
                return [
                    'status' => 'error',
                    'error' => 'Storage write/read mismatch',
                ];
            }

            $diskSpace = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $diskUsedPercent = round((($diskTotal - $diskSpace) / $diskTotal) * 100, 2);

            return [
                'status' => 'healthy',
                'disk_free_bytes' => $diskSpace,
                'disk_used_percent' => $diskUsedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $results
     */
    private function displayResults(array $results): void
    {
        $this->info('SentientX Health Check Results');
        $this->line('');

        $statusColor = match ($results['overall_status']) {
            'healthy' => 'info',
            'degraded' => 'warn',
            'unhealthy' => 'error',
            default => 'comment'
        };

        $this->$statusColor('Overall Status: '.strtoupper($results['overall_status']));
        $this->line("Timestamp: {$results['timestamp']}");
        $this->line('');

        $this->comment('Component Health:');
        $tableData = [];

        foreach ($results['checks'] as $component => $result) {
            $status = $result['status'];
            $details = [];

            if (isset($result['response_time_ms'])) {
                $details[] = "{$result['response_time_ms']}ms";
            }

            if (isset($result['error'])) {
                $details[] = "Error: {$result['error']}";
            }

            $tableData[] = [
                ucfirst(str_replace('_', ' ', $component)),
                strtoupper($status),
                implode(', ', $details),
            ];
        }

        $this->table(['Component', 'Status', 'Details'], $tableData);

        if ($results['overall_status'] !== 'healthy') {
            $this->line('');
            $this->warn('Some components are not healthy. Check the details above.');
        }
    }
}
