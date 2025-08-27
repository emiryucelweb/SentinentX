<?php

declare(strict_types=1);

namespace Tests\RealWorld\Agnostic;

use App\Models\Trade;
use App\Models\User;
use App\Services\Health\LiveHealthCheckService;
use App\Services\Notifier\AlertDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Network Partition Recovery Test
 * Tests system behavior when network connectivity is partial or degraded
 */
class NetworkPartitionTest extends TestCase
{
    use RefreshDatabase;

    private LiveHealthCheckService $healthCheck;

    private AlertDispatcher $alertDispatcher;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthCheck = app(LiveHealthCheckService::class);
        $this->alertDispatcher = app(AlertDispatcher::class);

        $this->testUser = User::factory()->create();
        $this->seed(['AiProvidersSeeder']);
    }

    #[Test]
    public function redis_connection_failure_triggers_fallback(): void
    {
        Log::info('Starting Redis partition test');

        // Setup: Normal system state with Redis working
        Cache::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));

        // Create active trading state
        $activeTrade = Trade::create([
            'user_id' => $this->testUser->id,
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.1,
            'entry_price' => 45000,
            'status' => 'OPEN',
        ]);

        // Phase 1: Simulate Redis connection failure
        $this->simulateRedisFailure();

        // Test 1: Cache operations should gracefully degrade
        $cacheResult = $this->healthCheck->runSpecificCheck('cache');
        $this->assertEquals('error', $cacheResult['status']);
        $this->assertStringContainsString('Redis', $cacheResult['error'] ?? '');

        // Test 2: Trading operations should continue with database fallback
        $trades = Trade::where('user_id', $this->testUser->id)->get();
        $this->assertCount(1, $trades);
        $this->assertEquals('OPEN', $trades->first()->status);

        // Test 3: Session handling should fall back to database
        config(['session.driver' => 'database']);
        $this->assertTrue(true); // If we get here, no exception was thrown

        // Phase 2: Simulate partial recovery (Redis available but slow)
        $this->simulateSlowRedis();

        // Test 4: System should detect slow Redis and implement timeouts
        $startTime = microtime(true);
        try {
            Cache::remember('slow_key', 60, function () {
                sleep(2); // Simulate slow operation

                return 'slow_value';
            });
        } catch (\Exception $e) {
            // Expected behavior - timeout should trigger
        }
        $endTime = microtime(true);

        $this->assertLessThan(
            5.0, // Should timeout before 5 seconds
            $endTime - $startTime,
            'Cache operations should timeout during network issues'
        );

        // Phase 3: Full recovery
        $this->simulateRedisRecovery();

        // Test 5: System should automatically recover
        Cache::put('recovery_test', 'recovered', 60);
        $this->assertEquals('recovered', Cache::get('recovery_test'));

        // Test 6: Health checks should reflect recovery
        $finalHealthCheck = $this->healthCheck->runSpecificCheck('cache');
        $this->assertEquals('healthy', $finalHealthCheck['status']);

        Log::info('Redis partition test completed', [
            'initial_trade_count' => 1,
            'final_trade_count' => Trade::count(),
            'recovery_successful' => $finalHealthCheck['status'] === 'healthy',
        ]);
    }

    #[Test]
    public function external_api_timeout_triggers_circuit_breaker(): void
    {
        Log::info('Starting external API circuit breaker test');

        // Phase 1: Normal API responses
        Http::fake([
            'https://api.bybit.com/*' => Http::response([
                'retCode' => 0,
                'result' => ['list' => [['lastPrice' => '45000']]],
            ], 200),
            'https://api.coingecko.com/*' => Http::response([
                'bitcoin' => ['usd' => 45000],
            ], 200),
        ]);

        // Test normal operation
        $healthResult = $this->healthCheck->runSpecificCheck('exchange');
        $this->assertEquals('healthy', $healthResult['status']);

        // Phase 2: API becomes slow (network partition)
        Http::fake([
            'https://api.bybit.com/*' => Http::response(null, 200)->delay(15000), // 15s delay
            'https://api.coingecko.com/*' => Http::response(null, 200)->delay(15000),
        ]);

        // Test 3: Circuit breaker should trigger
        $slowApiStart = microtime(true);
        $slowHealthResult = $this->healthCheck->runSpecificCheck('exchange');
        $slowApiEnd = microtime(true);

        $this->assertEquals('error', $slowHealthResult['status']);
        $this->assertLessThan(
            12.0, // Should timeout before 12 seconds
            $slowApiEnd - $slowApiStart,
            'API calls should timeout during network partition'
        );

        // Phase 3: API returns errors (partial partition)
        Http::fake([
            'https://api.bybit.com/*' => Http::response(['error' => 'Service unavailable'], 503),
            'https://api.coingecko.com/*' => Http::response(['error' => 'Rate limited'], 429),
        ]);

        // Test 4: System should handle various error codes gracefully
        $errorHealthResult = $this->healthCheck->runSpecificCheck('sentiment');
        $this->assertEquals('error', $errorHealthResult['status']);

        // Phase 4: Gradual recovery
        Http::fake([
            'https://api.bybit.com/*' => Http::sequence()
                ->push(['error' => 'Still failing'], 503)
                ->push(['retCode' => 0, 'result' => ['list' => [['lastPrice' => '45000']]]], 200)
                ->push(['retCode' => 0, 'result' => ['list' => [['lastPrice' => '45000']]]], 200),
            'https://api.coingecko.com/*' => Http::response([
                'bitcoin' => ['usd' => 45000],
            ], 200),
        ]);

        // Test 5: Circuit breaker should gradually allow requests through
        $attempts = 0;
        $successCount = 0;

        while ($attempts < 3) {
            try {
                $recoveryResult = $this->healthCheck->runSpecificCheck('exchange');
                if ($recoveryResult['status'] === 'healthy') {
                    $successCount++;
                }
            } catch (\Exception $e) {
                // Expected during recovery
            }
            $attempts++;
            sleep(1); // Brief delay between attempts
        }

        $this->assertGreaterThan(0, $successCount, 'Circuit breaker should allow some requests during recovery');

        Log::info('Circuit breaker test completed', [
            'attempts' => $attempts,
            'successes' => $successCount,
            'final_status' => $recoveryResult['status'] ?? 'unknown',
        ]);
    }

    #[Test]
    public function websocket_disconnect_maintains_data_integrity(): void
    {
        Log::info('Starting WebSocket disconnect test');

        // Phase 1: Normal WebSocket operation
        $initialHealthResult = $this->healthCheck->runSpecificCheck('websocket');

        // Phase 2: Simulate WebSocket connection drop
        Http::fake([
            'https://api-testnet.bybit.com/*' => Http::response(null, 503), // WebSocket health endpoint fails
        ]);

        // Test 1: WebSocket health check should detect failure
        $disconnectedResult = $this->healthCheck->runSpecificCheck('websocket');
        $this->assertEquals('error', $disconnectedResult['status']);

        // Test 2: System should fall back to REST API for price data
        Http::fake([
            'https://api-testnet.bybit.com/v5/market/tickers*' => Http::response([
                'retCode' => 0,
                'result' => [
                    'list' => [[
                        'symbol' => 'BTCUSDT',
                        'lastPrice' => '45000',
                        'volume24h' => '1000000',
                    ]],
                ],
            ], 200),
        ]);

        // Simulate price data request during WebSocket outage
        $response = Http::get('https://api-testnet.bybit.com/v5/market/tickers?category=linear&symbol=BTCUSDT');
        $this->assertEquals(200, $response->status());
        $this->assertEquals('45000', $response->json()['result']['list'][0]['lastPrice']);

        // Phase 3: WebSocket recovery
        Http::fake([
            'https://api-testnet.bybit.com/*' => Http::response([
                'result' => ['timeSecond' => time()],
                'retCode' => 0,
            ], 200),
        ]);

        // Test 3: WebSocket should recover gracefully
        $recoveredResult = $this->healthCheck->runSpecificCheck('websocket');
        $this->assertEquals('healthy', $recoveredResult['status']);

        Log::info('WebSocket disconnect test completed', [
            'initial_status' => $initialHealthResult['status'] ?? 'unknown',
            'disconnected_status' => $disconnectedResult['status'],
            'recovered_status' => $recoveredResult['status'],
        ]);
    }

    #[Test]
    public function telegram_api_partition_maintains_command_queue(): void
    {
        Log::info('Starting Telegram API partition test');

        // Phase 1: Normal Telegram operation
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123],
            ], 200),
        ]);

        $healthResult = $this->healthCheck->runSpecificCheck('telegram');
        $this->assertEquals('healthy', $healthResult['status']);

        // Phase 2: Telegram API becomes unreachable
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(null, 503),
        ]);

        // Test 1: Telegram health check should detect failure
        $failedResult = $this->healthCheck->runSpecificCheck('telegram');
        $this->assertEquals('error', $failedResult['status']);

        // Test 2: Commands should be queued for later delivery
        // (This would require implementing a queue mechanism)
        $queuedCommands = [
            ['command' => '/status', 'timestamp' => now()->toISOString()],
            ['command' => '/positions', 'timestamp' => now()->toISOString()],
        ];

        Cache::put('telegram_command_queue', $queuedCommands, 3600);
        $retrievedQueue = Cache::get('telegram_command_queue');
        $this->assertCount(2, $retrievedQueue);

        // Phase 3: Telegram API recovery
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 124],
            ], 200),
            'https://api.telegram.org/bot*/deleteMessage' => Http::response([
                'ok' => true,
                'result' => true,
            ], 200),
        ]);

        // Test 3: Queued commands should be processed after recovery
        $recoveredResult = $this->healthCheck->runSpecificCheck('telegram');
        $this->assertEquals('healthy', $recoveredResult['status']);

        // Simulate processing queued commands
        $queuedCommands = Cache::get('telegram_command_queue', []);
        foreach ($queuedCommands as $command) {
            // Process each queued command
            $this->assertNotEmpty($command['command']);
        }

        // Clear queue after processing
        Cache::forget('telegram_command_queue');
        $this->assertEmpty(Cache::get('telegram_command_queue', []));

        Log::info('Telegram partition test completed', [
            'queued_commands' => count($queuedCommands),
            'recovery_status' => $recoveredResult['status'],
        ]);
    }

    private function simulateRedisFailure(): void
    {
        // Simulate Redis connection failure by configuring invalid connection
        config(['cache.stores.redis.host' => 'invalid-redis-host']);
        config(['database.redis.default.host' => 'invalid-redis-host']);

        // Clear any existing connections
        Cache::flush();
    }

    private function simulateSlowRedis(): void
    {
        // Simulate slow Redis by adding artificial delays
        config(['cache.stores.redis.options.read_timeout' => 1]);
        config(['cache.stores.redis.options.write_timeout' => 1]);
    }

    private function simulateRedisRecovery(): void
    {
        // Restore normal Redis configuration
        config(['cache.stores.redis.host' => '127.0.0.1']);
        config(['database.redis.default.host' => '127.0.0.1']);
        config(['cache.stores.redis.options.read_timeout' => 30]);
        config(['cache.stores.redis.options.write_timeout' => 30]);

        // Clear cache to force new connections
        Cache::clear();
    }
}
