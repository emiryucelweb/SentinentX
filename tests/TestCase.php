<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\FreezesTime;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, FreezesTime;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministik test environment - Europe/Istanbul timezone
        $this->freeze('2025-01-01 00:00:00');

        // Test cache and session reset
        config(['cache.default' => 'array']);
        Cache::clear();

        // Redis test database flush (only test db)
        try {
            Redis::connection('default')->select(2); // test db
            Redis::flushDB();
        } catch (\Exception $e) {
            // Redis might not be available in all test environments
        }

        // HTTP dış servisleri default fake et - prevent stray requests
        Http::preventStrayRequests();

        // Comment out global sequence to prevent interference with specific tests
        // Http::fakeSequence() can override specific Http::fake() calls

        // Default fakes for external services
        Http::fake([
            'https://api.bybit.com/*' => Http::response([
                'retCode' => 0,
                'result' => [
                    'price' => '50000.0',
                    'time' => now()->timestamp * 1000,
                    'list' => [],
                ],
            ], 200),
            'https://api-testnet.bybit.com/*' => Http::response([
                'retCode' => 0,
                'retMsg' => 'OK',
                'result' => [
                    'orderId' => 'default_order_id',
                    'price' => '50000.0',
                    'time' => now()->timestamp * 1000,
                ],
            ], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"action":"NONE","confidence":95}']]]]],
            ], 200),
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"NONE","confidence":95}']]],
            ], 200),
            'https://api.coingecko.com/*' => Http::response([
                'bitcoin' => ['usd' => 50000, 'usd_24h_change' => 2.5],
                'ethereum' => ['usd' => 3000, 'usd_24h_change' => 1.8],
            ], 200),
            '*' => Http::response(['ok' => true, 'data' => []], 200),
        ]);
    }

    protected function tearDown(): void
    {
        // Test sonrası temizlik
        $this->unfreeze(); // Carbon reset via trait
        Http::fake(); // HTTP fakes temizle

        parent::tearDown();
    }

    /**
     * Helper to assert HTTP calls with detailed debugging
     */
    protected function assertHttpCallCount(int $expected, string $context = ''): void
    {
        try {
            Http::assertSentCount($expected);
        } catch (\Exception $e) {
            $this->fail("HTTP call count assertion failed{$context}: {$e->getMessage()}");
        }
    }

    /**
     * Helper to assert specific AI provider endpoints were called
     */
    protected function assertAiProviderCalled(string $provider, int $times = 1): void
    {
        $patterns = [
            'openai' => 'https://api.openai.com/*',
            'gemini' => 'https://generativelanguage.googleapis.com/*',
            'grok' => 'https://api.x.ai/*',
        ];

        if (! isset($patterns[$provider])) {
            $this->fail("Unknown AI provider: {$provider}");
        }

        Http::assertSent(function ($request) use ($patterns, $provider) {
            return str_contains($request->url(), str_replace('/*', '', $patterns[$provider]));
        }, $times);
    }
}
