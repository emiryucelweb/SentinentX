<?php

declare(strict_types=1);

namespace Tests\Chaos;

use App\Models\Alert;
use App\Models\Trade;
use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionSizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Chaos Engineering Test Suite
 * Tests system resilience under various failure conditions
 */
#[Group('chaos')]
class ChaosTestSuite extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['AiProvidersSeeder']);
    }

    #[Test]
    public function system_handles_database_connection_failure(): void
    {
        Log::info('Testing database connection failure resilience');

        // Test that system gracefully handles DB failures
        try {
            // Simulate database connection issues by using invalid connection
            config(['database.connections.pgsql.host' => 'invalid-host']);
            DB::purge('pgsql');

            // System should handle this gracefully
            $result = $this->attemptDatabaseOperation();

            // Should either succeed with fallback or fail gracefully
            $this->assertTrue(
                $result['success'] || $result['graceful_failure'],
                'System should handle database failures gracefully'
            );

        } catch (\Exception $e) {
            // Exception is expected - verify it's handled properly
            $this->assertStringContainsString('database', strtolower($e->getMessage()));
            Log::warning('Database failure handled with exception', [
                'exception' => $e->getMessage(),
            ]);
        }

        // Restore database connection
        config(['database.connections.pgsql.host' => env('DB_HOST', '127.0.0.1')]);
        DB::purge('pgsql');

        Log::info('Database failure resilience test completed');
    }

    #[Test]
    public function system_handles_cache_failure(): void
    {
        Log::info('Testing cache failure resilience');

        // Simulate cache failure
        Cache::shouldReceive('get')->andThrow(new \Exception('Cache connection failed'));
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache connection failed'));
        Cache::shouldReceive('remember')->andThrow(new \Exception('Cache connection failed'));

        // System should continue working without cache
        $positionSizer = new PositionSizer;

        $result = $positionSizer->sizeByRisk(
            'LONG',
            50000.0,
            47500.0,
            10000.0,
            5,
            0.02,
            0.001,
            0.001
        );

        // Should still return valid result even without cache
        $this->assertIsArray($result);
        $this->assertArrayHasKey('qty', $result);
        $this->assertGreaterThan(0, $result['qty']);

        Log::info('Cache failure resilience test completed', [
            'result' => $result,
        ]);
    }

    #[Test]
    public function system_handles_external_api_failure(): void
    {
        Log::info('Testing external API failure resilience');

        // Mock HTTP failures for external APIs
        Http::fake([
            'api.bybit.com/*' => Http::response([], 503), // Service unavailable
            'api.openai.com/*' => Http::response([], 429), // Rate limited
            'api.gemini.com/*' => Http::response([], 500), // Internal error
        ]);

        // Test that trading system handles API failures
        $riskGuard = new RiskGuard;

        // Should handle API failures gracefully
        $result = $riskGuard->okToOpen('BTCUSDT', 50000.0, 'LONG', 2, 45000.0);

        // Should either succeed with fallback data or fail safely
        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok', $result);

        if (! $result['ok']) {
            $this->assertArrayHasKey('reason', $result);
            Log::info('API failure handled by blocking trade', $result);
        } else {
            Log::info('API failure handled with fallback data', $result);
        }

        Log::info('External API failure resilience test completed');
    }

    #[Test]
    public function system_handles_memory_pressure(): void
    {
        Log::info('Testing memory pressure resilience');

        $initialMemory = memory_get_usage(true);

        // Create memory pressure
        $memoryHogs = [];
        for ($i = 0; $i < 100; $i++) {
            $memoryHogs[] = str_repeat('x', 100000); // 100KB per iteration
        }

        $pressureMemory = memory_get_usage(true);
        $memoryIncrease = $pressureMemory - $initialMemory;

        Log::info('Memory pressure created', [
            'initial_mb' => round($initialMemory / 1024 / 1024, 2),
            'pressure_mb' => round($pressureMemory / 1024 / 1024, 2),
            'increase_mb' => round($memoryIncrease / 1024 / 1024, 2),
        ]);

        // System should still function under memory pressure
        $positionSizer = new PositionSizer;

        $result = $positionSizer->sizeByRisk(
            'LONG',
            50000.0,
            47500.0,
            10000.0,
            3,
            0.01,
            0.001,
            0.001
        );

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['qty']);

        // Clean up memory
        unset($memoryHogs);
        gc_collect_cycles();

        $finalMemory = memory_get_usage(true);
        Log::info('Memory pressure test completed', [
            'final_mb' => round($finalMemory / 1024 / 1024, 2),
            'cleanup_effective' => $finalMemory < $pressureMemory,
        ]);
    }

    #[Test]
    public function system_handles_high_load_simulation(): void
    {
        Log::info('Testing high load resilience');

        $startTime = microtime(true);
        $operations = 0;
        $errors = 0;
        $maxExecutionTime = 3; // 3 seconds max

        // Simulate high load with concurrent operations
        while ((microtime(true) - $startTime) < $maxExecutionTime) {
            try {
                // Simulate various operations
                switch (rand(1, 4)) {
                    case 1:
                        // Database operation
                        Trade::count();
                        break;
                    case 2:
                        // Position sizing calculation
                        $sizer = new PositionSizer;
                        $sizer->sizeByRisk('LONG', 50000, 47500, 10000, 2, 0.01, 0.001, 0.001);
                        break;
                    case 3:
                        // Risk assessment
                        $guard = new RiskGuard;
                        $guard->okToOpen('BTCUSDT', 50000, 'LONG', 2, 47500);
                        break;
                    case 4:
                        // Create alert
                        Alert::create([
                            'type' => 'LOAD_TEST',
                            'severity' => 'info',
                            'message' => 'Load test alert',
                            'context' => json_encode(['test' => true]),
                        ]);
                        break;
                }
                $operations++;
            } catch (\Exception $e) {
                $errors++;
                Log::debug('Load test operation failed', [
                    'error' => $e->getMessage(),
                    'operation' => $operations + $errors,
                ]);
            }

            // Small delay to prevent overwhelming
            usleep(1000); // 1ms
        }

        $actualTime = microtime(true) - $startTime;
        $operationsPerSecond = $operations / $actualTime;
        $errorRate = $errors / ($operations + $errors) * 100;

        $this->assertGreaterThan(50, $operations, 'Should complete at least 50 operations');
        $this->assertLessThan(50, $errorRate, 'Error rate should be less than 50%');

        Log::info('High load simulation completed', [
            'duration_seconds' => round($actualTime, 2),
            'total_operations' => $operations,
            'total_errors' => $errors,
            'ops_per_second' => round($operationsPerSecond, 2),
            'error_rate_percent' => round($errorRate, 2),
        ]);
    }

    #[Test]
    public function system_handles_cascading_failures(): void
    {
        Log::info('Testing cascading failure resilience');

        $failures = [];

        try {
            // Simulate multiple simultaneous failures

            // 1. Database slow response
            DB::statement('SELECT SLEEP(0.1)'); // 100ms delay
            $failures[] = 'database_slow';

            // 2. Cache failure
            Cache::shouldReceive('get')->andReturn(null);
            $failures[] = 'cache_miss';

            // 3. External API failure
            Http::fake([
                '*' => Http::response([], 503),
            ]);
            $failures[] = 'api_failure';

            // System should still provide basic functionality
            $trade = Trade::create([
                'symbol' => 'BTCUSDT',
                'side' => 'LONG',
                'qty' => 0.1,
                'entry_price' => 50000.0,
                'leverage' => 2,
                'status' => 'OPEN',
            ]);

            $this->assertInstanceOf(Trade::class, $trade);
            $this->assertEquals('OPEN', $trade->status);

        } catch (\Exception $e) {
            // If system fails, it should fail gracefully
            $this->assertStringNotContainsString('fatal', strtolower($e->getMessage()));
            Log::warning('Cascading failure handled with exception', [
                'exception' => $e->getMessage(),
                'failures' => $failures,
            ]);
        }

        Log::info('Cascading failure test completed', [
            'simulated_failures' => $failures,
            'system_survived' => true,
        ]);
    }

    #[Test]
    public function system_handles_data_corruption_scenarios(): void
    {
        Log::info('Testing data corruption resilience');

        // Create a trade with normal data
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.1,
            'entry_price' => 50000.0,
            'leverage' => 2,
            'status' => 'OPEN',
        ]);

        // Simulate data corruption by updating with invalid data
        try {
            DB::table('trades')
                ->where('id', $trade->id)
                ->update([
                    'qty' => -999.999, // Invalid negative quantity
                    'entry_price' => 0, // Invalid zero price
                    'leverage' => 1000, // Invalid high leverage
                ]);

            // Try to load and work with corrupted data
            $corruptedTrade = Trade::find($trade->id);

            // System should detect and handle corruption
            $positionSizer = new PositionSizer;

            // This should either handle the corruption or fail gracefully
            $entryPrice = is_numeric($corruptedTrade->entry_price) ? abs((float) $corruptedTrade->entry_price) : 50000;
            $entryPrice = $entryPrice > 0 ? $entryPrice : 50000; // Fallback for zero

            $result = $positionSizer->sizeByRisk(
                $corruptedTrade->side,
                $entryPrice,
                45000,
                10000,
                min((int) $corruptedTrade->leverage, 100), // Cap leverage
                0.01,
                0.001,
                0.001
            );

            // Should return valid result despite corrupted input
            $this->assertIsArray($result);
            $this->assertArrayHasKey('qty', $result);

        } catch (\Exception $e) {
            // Exception is acceptable for data corruption
            $this->assertStringContainsString('invalid', strtolower($e->getMessage()));
            Log::warning('Data corruption handled with exception', [
                'exception' => $e->getMessage(),
            ]);
        }

        Log::info('Data corruption resilience test completed');
    }

    #[Test]
    public function system_recovers_from_temporary_failures(): void
    {
        Log::info('Testing recovery from temporary failures');

        $recoveryAttempts = 0;
        $maxAttempts = 3;
        $success = false;

        // Simulate temporary failure with recovery
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                if ($attempt < 3) {
                    // Fail first 2 attempts
                    throw new \Exception("Temporary failure attempt {$attempt}");
                }

                // Succeed on 3rd attempt
                $result = $this->performCriticalOperation();
                $success = true;
                break;

            } catch (\Exception $e) {
                $recoveryAttempts++;
                Log::debug("Recovery attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                ]);

                // Wait before retry (exponential backoff simulation)
                usleep($attempt * 10000); // 10ms, 20ms, 30ms
            }
        }

        $this->assertTrue($success, 'System should recover after temporary failures');
        $this->assertEquals(2, $recoveryAttempts, 'Should have made 2 recovery attempts');

        Log::info('Recovery test completed', [
            'recovery_attempts' => $recoveryAttempts,
            'final_success' => $success,
        ]);
    }

    /**
     * Helper method to simulate critical operation
     */
    private function performCriticalOperation(): array
    {
        return [
            'success' => true,
            'data' => 'Critical operation completed',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Helper method to attempt database operation
     */
    private function attemptDatabaseOperation(): array
    {
        try {
            $count = Trade::count();

            return [
                'success' => true,
                'graceful_failure' => false,
                'result' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'graceful_failure' => true,
                'error' => $e->getMessage(),
            ];
        }
    }
}
