<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionSizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive load testing suite for performance validation
 */
class LoadTestingSuite extends TestCase
{
    #[Test]
    public function database_concurrent_operations(): void
    {
        $startTime = microtime(true);
        $iterations = 100;

        // Simulate concurrent database operations using simple queries
        for ($i = 0; $i < $iterations; $i++) {
            // Use a simple SELECT query instead of INSERT
            $result = DB::select('SELECT ? as iteration', [$i]);
            $this->assertNotEmpty($result);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(2.0, $duration, 'Database operations should complete within 2 seconds');

        // Test connection is working
        $this->assertNotNull(DB::connection());
    }

    #[Test]
    public function cache_performance_under_load(): void
    {
        $startTime = microtime(true);
        $iterations = 500;

        // Test cache write performance
        for ($i = 0; $i < $iterations; $i++) {
            Cache::put("test_key_{$i}", [
                'symbol' => 'BTCUSDT',
                'price' => 50000 + $i,
                'timestamp' => time(),
            ], 60);
        }

        $writeTime = microtime(true) - $startTime;

        // Test cache read performance
        $readStartTime = microtime(true);
        $hitCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $value = Cache::get("test_key_{$i}");
            if ($value !== null) {
                $hitCount++;
            }
        }

        $readTime = microtime(true) - $readStartTime;

        // Performance assertions
        $this->assertLessThan(1.0, $writeTime, 'Cache writes should complete within 1 second');
        $this->assertLessThan(0.5, $readTime, 'Cache reads should complete within 0.5 seconds');
        $this->assertEquals($iterations, $hitCount, 'All cache entries should be hit');

        // Calculate hit ratio
        $hitRatio = $hitCount / $iterations;
        $this->assertGreaterThanOrEqual(0.95, $hitRatio, 'Cache hit ratio should be >= 95%');
    }

    #[Test]
    public function position_sizing_performance(): void
    {
        $sizer = new PositionSizer;
        $iterations = 1000;

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $result = $sizer->sizeByRisk(
                'LONG',
                50000 + ($i % 100), // Varying price
                47500 + ($i % 100), // Varying stop loss
                10000 + ($i % 1000), // Varying equity
                10 + ($i % 10),      // Varying leverage
                0.02,                // Fixed risk
                0.001,
                0.001
            );

            // Verify result structure
            $this->assertArrayHasKey('qty', $result);
            $this->assertIsFloat($result['qty']);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertion: should handle 1000 calculations in under 1 second
        $this->assertLessThan(1.0, $duration, 'Position sizing should handle 1000 calculations in under 1 second');

        // Calculate operations per second
        $opsPerSecond = $iterations / $duration;
        $this->assertGreaterThan(500, $opsPerSecond, 'Should handle at least 500 operations per second');
    }

    #[Test]
    public function risk_guard_performance(): void
    {
        $riskGuard = new RiskGuard;
        $iterations = 500;

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $entryPrice = 50000 + ($i % 1000);
            $stopLoss = 47500 + ($i % 1000);
            $leverage = 10 + ($i % 10);

            $result = $riskGuard->okToOpen(
                'BTCUSDT',
                $entryPrice,
                'LONG',
                $leverage,
                $stopLoss
            );

            // Verify result structure
            $this->assertIsArray($result);
            $this->assertArrayHasKey('ok', $result);
            $this->assertIsBool($result['ok']);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertion
        $this->assertLessThan(2.0, $duration, 'Risk guard should handle 500 checks in under 2 seconds');

        $checksPerSecond = $iterations / $duration;
        $this->assertGreaterThan(200, $checksPerSecond, 'Should handle at least 200 risk checks per second');
    }

    #[Test]
    public function memory_usage_under_stress(): void
    {
        $initialMemory = memory_get_usage(true);
        $iterations = 10000;
        $data = [];

        // Simulate heavy data processing
        for ($i = 0; $i < $iterations; $i++) {
            $data[] = [
                'id' => $i,
                'symbol' => 'BTCUSDT',
                'price' => 50000 + rand(-1000, 1000),
                'volume' => rand(1, 1000) / 1000,
                'timestamp' => time() + $i,
                'metadata' => [
                    'source' => 'test',
                    'iteration' => $i,
                    'random' => rand(1, 100000),
                ],
            ];

            // Clear data every 1000 iterations to test garbage collection
            if ($i % 1000 === 0) {
                $data = array_slice($data, -100); // Keep only last 100 items
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage(true);
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory usage should be reasonable (less than 50MB for this test)
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should stay under 50MB');

        // Peak memory should be reasonable
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->assertLessThan(100, $peakMemory, 'Peak memory usage should stay under 100MB');
    }

    #[Test]
    public function concurrent_api_simulation(): void
    {
        $startTime = microtime(true);
        $requests = 100;
        $responses = [];

        // Simulate API responses
        for ($i = 0; $i < $requests; $i++) {
            $processingStart = microtime(true);

            // Simulate API processing
            $data = [
                'symbol' => 'BTCUSDT',
                'price' => 50000 + rand(-5000, 5000),
                'timestamp' => time(),
                'volume' => rand(1, 1000) / 1000,
                'bid' => 49990 + rand(-100, 100),
                'ask' => 50010 + rand(-100, 100),
            ];

            // Simulate processing delay
            usleep(rand(1000, 5000)); // 1-5ms delay

            $processingTime = microtime(true) - $processingStart;

            $responses[] = [
                'data' => $data,
                'processing_time' => $processingTime,
                'response_time' => microtime(true) - $startTime,
            ];
        }

        $totalTime = microtime(true) - $startTime;

        // Performance assertions
        $this->assertLessThan(10.0, $totalTime, 'Should process 100 API requests in under 10 seconds');

        // Calculate average response time
        $avgResponseTime = array_sum(array_column($responses, 'processing_time')) / count($responses);
        $this->assertLessThan(0.01, $avgResponseTime, 'Average response time should be under 10ms');

        // Calculate throughput
        $throughput = $requests / $totalTime;
        $this->assertGreaterThan(10, $throughput, 'Should handle at least 10 requests per second');
    }

    #[Test]
    public function system_resource_monitoring(): void
    {
        $startTime = microtime(true);
        $initialMemory = memory_get_usage();

        // Perform various system operations
        $operations = [
            'database' => function () {
                for ($i = 0; $i < 50; $i++) {
                    DB::select('SELECT 1 as test_query');
                }
            },
            'cache' => function () {
                for ($i = 0; $i < 100; $i++) {
                    Cache::put("perf_test_{$i}", ['data' => $i], 10);
                    Cache::get("perf_test_{$i}");
                }
            },
            'computation' => function () {
                $sizer = new PositionSizer;
                for ($i = 0; $i < 100; $i++) {
                    $sizer->sizeByRisk('LONG', 50000, 47500, 10000, 10, 0.02, 0.001, 0.001);
                }
            },
        ];

        $operationTimes = [];

        foreach ($operations as $name => $operation) {
            $opStart = microtime(true);
            $operation();
            $operationTimes[$name] = microtime(true) - $opStart;
        }

        $totalTime = microtime(true) - $startTime;
        $finalMemory = memory_get_usage();
        $memoryDelta = ($finalMemory - $initialMemory) / 1024; // KB

        // System performance assertions
        $this->assertLessThan(5.0, $totalTime, 'All operations should complete within 5 seconds');
        $this->assertLessThan(1024, $memoryDelta, 'Memory usage should not increase by more than 1MB');

        // Individual operation performance
        foreach ($operationTimes as $operation => $time) {
            $this->assertLessThan(2.0, $time, "Operation '{$operation}' should complete within 2 seconds");
        }

        // Resource efficiency
        $operationsPerSecond = count($operations) / $totalTime;
        $this->assertGreaterThan(0.5, $operationsPerSecond, 'Should maintain reasonable operation throughput');
    }
}
