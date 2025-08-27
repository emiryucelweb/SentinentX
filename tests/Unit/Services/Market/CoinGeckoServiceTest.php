<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Market;

use App\Services\Market\CoinGeckoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoinGeckoServiceTest extends TestCase
{
    private CoinGeckoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CoinGeckoService;
        Cache::flush();
    }

    public function test_can_fetch_multi_coin_data(): void
    {
        // Mock CoinGecko API response
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'current_price' => 43250.0,
                    'market_cap' => 850000000000,
                    'market_cap_rank' => 1,
                    'total_volume' => 25000000000,
                    'price_change_percentage_24h' => 2.5,
                    'price_change_percentage_7d_in_currency' => 1.5,
                    'ath' => 69000.0,
                    'ath_change_percentage' => -37.3,
                    'atl' => 67.81,
                    'atl_change_percentage' => 63600.0,
                    'circulating_supply' => 19500000,
                    'total_supply' => 21000000,
                    'max_supply' => 21000000,
                    'sparkline_in_7d' => ['price' => [43000, 43100, 43250]],
                    'last_updated' => '2024-01-20T12:00:00.000Z',
                ],
                [
                    'id' => 'ethereum',
                    'name' => 'Ethereum',
                    'current_price' => 2650.0,
                    'market_cap' => 320000000000,
                    'market_cap_rank' => 2,
                    'total_volume' => 15000000000,
                    'price_change_percentage_24h' => 1.8,
                    'price_change_percentage_7d_in_currency' => 2.1,
                    'ath' => 4878.26,
                    'ath_change_percentage' => -45.7,
                    'atl' => 0.432979,
                    'atl_change_percentage' => 612000.0,
                    'circulating_supply' => 120000000,
                    'total_supply' => 120000000,
                    'max_supply' => null,
                    'sparkline_in_7d' => ['price' => [2600, 2625, 2650]],
                    'last_updated' => '2024-01-20T12:00:00.000Z',
                ],
                [
                    'id' => 'solana',
                    'name' => 'Solana',
                    'current_price' => 98.5,
                    'market_cap' => 43000000000,
                    'market_cap_rank' => 5,
                    'total_volume' => 2500000000,
                    'price_change_percentage_24h' => 3.2,
                    'price_change_percentage_7d_in_currency' => 4.1,
                    'ath' => 260.0,
                    'ath_change_percentage' => -62.1,
                    'atl' => 0.5,
                    'atl_change_percentage' => 19600.0,
                    'circulating_supply' => 435000000,
                    'total_supply' => 435000000,
                    'max_supply' => null,
                    'sparkline_in_7d' => ['price' => [95.5, 97.0, 98.5]],
                    'last_updated' => '2024-01-20T12:00:00.000Z',
                ],
                [
                    'id' => 'ripple',
                    'name' => 'XRP',
                    'current_price' => 0.58,
                    'market_cap' => 32000000000,
                    'market_cap_rank' => 6,
                    'total_volume' => 1800000000,
                    'price_change_percentage_24h' => 1.2,
                    'price_change_percentage_7d_in_currency' => -0.5,
                    'ath' => 3.84,
                    'ath_change_percentage' => -84.9,
                    'atl' => 0.0028,
                    'atl_change_percentage' => 20600.0,
                    'circulating_supply' => 55000000000,
                    'total_supply' => 99000000000,
                    'max_supply' => 100000000000,
                    'sparkline_in_7d' => ['price' => [0.57, 0.575, 0.58]],
                    'last_updated' => '2024-01-20T12:00:00.000Z',
                ],
            ]),
        ]);

        $result = $this->service->getMultiCoinData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('BTCUSDT', $result);
        $this->assertArrayHasKey('ETHUSDT', $result);
        $this->assertArrayHasKey('SOLUSDT', $result);
        $this->assertArrayHasKey('XRPUSDT', $result);

        $btcData = $result['BTCUSDT'];
        $this->assertEquals('BTCUSDT', $btcData['symbol']);

        // Test basic structure and data types (graceful degradation)
        $this->assertArrayHasKey('name', $btcData);
        $this->assertArrayHasKey('current_price', $btcData);
        $this->assertArrayHasKey('reliability_score', $btcData);
        $this->assertArrayHasKey('sentiment', $btcData);

        $this->assertIsString($btcData['name']);
        $this->assertIsFloat($btcData['current_price']);
        $this->assertIsFloat($btcData['reliability_score']);
        $this->assertIsFloat($btcData['sentiment']);

        // Test that reliability and sentiment scores are calculated
        $this->assertGreaterThanOrEqual(0, $btcData['reliability_score']);
        $this->assertGreaterThanOrEqual(0, $btcData['sentiment']);

        $ethData = $result['ETHUSDT'];
        $this->assertEquals('ETHUSDT', $ethData['symbol']);
        $this->assertIsString($ethData['name']);
        $this->assertIsFloat($ethData['current_price']);
    }

    public function test_handles_api_failure_gracefully(): void
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([], 500),
        ]);

        $result = $this->service->getMultiCoinData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('BTCUSDT', $result);
        $this->assertEquals(25.0, $result['BTCUSDT']['reliability_score']); // Fallback value
    }

    public function test_calculates_reliability_score_correctly(): void
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'current_price' => 43250.0,
                    'market_cap' => 850000000000,
                    'market_cap_rank' => 1, // High rank = high score
                    'total_volume' => 25000000000,
                    'price_change_percentage_24h' => 2.5, // Low volatility = high score
                    'ath' => 50000.0,
                    'ath_change_percentage' => -13.5, // Close to ATH = high score
                ],
            ]),
        ]);

        $result = $this->service->getMultiCoinData();
        $btcData = $result['BTCUSDT'];

        // Bitcoin should have high reliability (rank 1, low volatility)
        $this->assertGreaterThan(70, $btcData['reliability_score']);
    }

    public function test_calculates_sentiment_score(): void
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'current_price' => 43250.0,
                    'market_cap' => 850000000000,
                    'market_cap_rank' => 1,
                    'total_volume' => 25000000000,
                    'price_change_percentage_24h' => 5.0, // Positive change = bullish sentiment
                    'price_change_percentage_7d_in_currency' => 3.0,
                    'ath' => 50000.0,
                    'ath_change_percentage' => -13.5,
                    'atl' => 67.81,
                    'sparkline_in_7d' => ['price' => [43000, 43100, 43250]],
                    'last_updated' => '2024-01-20T12:00:00.000Z',
                ],
            ]),
        ]);

        $result = $this->service->getMultiCoinData();
        $btcData = $result['BTCUSDT'];

        // With 5% positive change, sentiment should be > 50
        $this->assertGreaterThan(50, $btcData['sentiment']);
        $this->assertLessThanOrEqual(100, $btcData['sentiment']);
    }

    public function test_uses_cache_for_performance(): void
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'current_price' => 43250.0,
                ],
            ]),
        ]);

        // First call
        $result1 = $this->service->getMultiCoinData();

        // Second call should use cache
        $result2 = $this->service->getMultiCoinData();

        $this->assertEquals($result1, $result2);

        // Should only make one HTTP request
        Http::assertSentCount(1);
    }

    public function test_single_coin_data_fetch(): void
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/bitcoin*' => Http::response([
                'id' => 'bitcoin',
                'name' => 'Bitcoin',
                'description' => ['en' => 'Bitcoin is a cryptocurrency...'],
                'market_data' => [
                    'current_price' => ['usd' => 43250.0],
                    'market_cap' => ['usd' => 850000000000],
                    'total_volume' => ['usd' => 25000000000],
                    'price_change_percentage_24h' => 2.5,
                    'price_change_percentage_7d' => 1.8,
                    'price_change_percentage_30d' => 5.2,
                    'ath' => ['usd' => 69000.0],
                    'atl' => ['usd' => 67.81],
                ],
                'developer_score' => 85.5,
                'community_score' => 78.2,
                'liquidity_score' => 92.1,
                'public_interest_score' => 88.3,
            ]),
        ]);

        $result = $this->service->getCoinData('BTCUSDT');

        $this->assertIsArray($result);
        $this->assertEquals('BTCUSDT', $result['symbol']);
        $this->assertEquals('Bitcoin', $result['name']);
        $this->assertEquals(43250.0, $result['current_price']);
        $this->assertEquals(85.5, $result['developer_score']);
        $this->assertEquals(78.2, $result['community_score']);
        $this->assertGreaterThan(0, $result['reliability_score']);
    }

    public function test_handles_unsupported_symbol(): void
    {
        $result = $this->service->getCoinData('UNSUPPORTED');

        $this->assertIsArray($result);
        $this->assertEquals('UNSUPPORTED', $result['symbol']);
        $this->assertEquals(25.0, $result['reliability_score']); // Default low score
        $this->assertArrayHasKey('error', $result);
    }
}
