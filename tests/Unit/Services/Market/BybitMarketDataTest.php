<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Market;

use App\Services\Exchange\BybitClient;
use App\Services\Market\BybitMarketData;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class BybitMarketDataTest extends TestCase
{
    private BybitMarketData $marketData;

    private BybitClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(BybitClient::class);
        $this->marketData = new BybitMarketData($this->mockClient);

        Cache::flush();
    }

    public function test_get_klines_success(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => [
                'list' => [
                    [1703980800000, '43000.0', '43100.0', '42900.0', '43050.0', '100.5', '4305000.0'],
                    [1703980860000, '43050.0', '43200.0', '43000.0', '43150.0', '95.3', '4110000.0'],
                ]
            ]
        ];

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/kline', Mockery::subset([
                            'category' => 'linear',
                            'symbol' => 'BTCUSDT',
                            'interval' => '1',
                            'limit' => 200,
                        ]))
                        ->andReturn($mockResponse);

        $result = $this->marketData->getKlines('BTCUSDT');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('BTCUSDT', $result['symbol']);
        $this->assertEquals('1', $result['interval']);

        // Check first kline structure
        $firstKline = $result['data'][0];
        $this->assertEquals(1703980800, $firstKline['timestamp']);
        $this->assertEquals(43000.0, $firstKline['open']);
        $this->assertEquals(43100.0, $firstKline['high']);
        $this->assertEquals(42900.0, $firstKline['low']);
        $this->assertEquals(43050.0, $firstKline['close']);
        $this->assertEquals(100.5, $firstKline['volume']);
        $this->assertEquals(4305000.0, $firstKline['turnover']);
    }

    public function test_get_klines_with_time_range(): void
    {
        $startTime = 1703980800;
        $endTime = 1703984400;

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/kline', Mockery::subset([
                            'category' => 'linear',
                            'symbol' => 'ETHUSDT',
                            'interval' => '5',
                            'limit' => 100,
                            'start' => $startTime * 1000,
                            'end' => $endTime * 1000,
                        ]))
                        ->andReturn([
                            'success' => true,
                            'result' => ['list' => []]
                        ]);

        $result = $this->marketData->getKlines('ETHUSDT', '5', 100, $startTime, $endTime);

        $this->assertTrue($result['success']);
    }

    public function test_get_klines_api_failure(): void
    {
        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->andReturn([
                            'success' => false,
                            'error' => 'API Error'
                        ]);

        $result = $this->marketData->getKlines('BTCUSDT');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['data']);
        $this->assertEquals('Failed to fetch klines', $result['error']);
    }

    public function test_get_klines_exception_handling(): void
    {
        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->andThrow(new \Exception('Network error'));

        $result = $this->marketData->getKlines('BTCUSDT');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['data']);
        $this->assertEquals('Network error', $result['error']);
    }

    public function test_get_ticker_success(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => [
                'list' => [
                    [
                        'symbol' => 'BTCUSDT',
                        'lastPrice' => '43000.0',
                        'bid1Price' => '42999.0',
                        'ask1Price' => '43001.0',
                        'volume24h' => '1000.5',
                        'turnover24h' => '43000000.0',
                        'price24hPcnt' => '0.025',
                        'highPrice24h' => '44000.0',
                        'lowPrice24h' => '42000.0',
                    ]
                ]
            ]
        ];

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/tickers', [
                            'category' => 'linear',
                            'symbol' => 'BTCUSDT'
                        ])
                        ->andReturn($mockResponse);

        $result = $this->marketData->getTicker('BTCUSDT');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);

        $ticker = $result['data'];
        $this->assertEquals('BTCUSDT', $ticker['symbol']);
        $this->assertEquals(43000.0, $ticker['last_price']);
        $this->assertEquals(42999.0, $ticker['bid']);
        $this->assertEquals(43001.0, $ticker['ask']);
        $this->assertEquals(1000.5, $ticker['volume_24h']);
        $this->assertEquals(43000000.0, $ticker['turnover_24h']);
        $this->assertEquals(0.025, $ticker['price_change_24h']);
        $this->assertEquals(44000.0, $ticker['high_24h']);
        $this->assertEquals(42000.0, $ticker['low_24h']);
        $this->assertIsInt($ticker['timestamp']);
    }

    public function test_get_ticker_failure(): void
    {
        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->andReturn([
                            'success' => false,
                            'error' => 'Symbol not found'
                        ]);

        $result = $this->marketData->getTicker('INVALID');

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertEquals('Failed to fetch ticker', $result['error']);
    }

    public function test_get_orderbook_success(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => [
                's' => 'BTCUSDT',
                'ts' => 1703980800000,
                'u' => 12345,
                'b' => [
                    ['42999.0', '1.5'],
                    ['42998.0', '2.0'],
                ],
                'a' => [
                    ['43001.0', '1.2'],
                    ['43002.0', '1.8'],
                ],
            ]
        ];

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/orderbook', [
                            'category' => 'linear',
                            'symbol' => 'BTCUSDT',
                            'limit' => 25
                        ])
                        ->andReturn($mockResponse);

        $result = $this->marketData->getOrderbook('BTCUSDT');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);

        $orderbook = $result['data'];
        $this->assertEquals('BTCUSDT', $orderbook['symbol']);
        $this->assertEquals(1703980800, $orderbook['timestamp']);
        $this->assertEquals(12345, $orderbook['update_id']);

        $this->assertCount(2, $orderbook['bids']);
        $this->assertCount(2, $orderbook['asks']);

        // Check bid structure
        $this->assertEquals([42999.0, 1.5], $orderbook['bids'][0]);
        $this->assertEquals([42998.0, 2.0], $orderbook['bids'][1]);

        // Check ask structure
        $this->assertEquals([43001.0, 1.2], $orderbook['asks'][0]);
        $this->assertEquals([43002.0, 1.8], $orderbook['asks'][1]);
    }

    public function test_get_multiple_tickers_success(): void
    {
        $symbols = ['BTCUSDT', 'ETHUSDT'];
        
        $mockResponse = [
            'success' => true,
            'result' => [
                'list' => [
                    [
                        'symbol' => 'BTCUSDT',
                        'lastPrice' => '43000.0',
                        'bid1Price' => '42999.0',
                        'ask1Price' => '43001.0',
                        'volume24h' => '1000.5',
                        'price24hPcnt' => '0.025',
                    ],
                    [
                        'symbol' => 'ETHUSDT',
                        'lastPrice' => '2500.0',
                        'bid1Price' => '2499.5',
                        'ask1Price' => '2500.5',
                        'volume24h' => '500.0',
                        'price24hPcnt' => '0.015',
                    ],
                    [
                        'symbol' => 'ADAUSDT', // Should be filtered out
                        'lastPrice' => '0.5',
                        'bid1Price' => '0.499',
                        'ask1Price' => '0.501',
                        'volume24h' => '100.0',
                        'price24hPcnt' => '0.01',
                    ],
                ]
            ]
        ];

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/tickers', ['category' => 'linear'])
                        ->andReturn($mockResponse);

        $result = $this->marketData->getMultipleTickers($symbols);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['count']);

        $this->assertArrayHasKey('BTCUSDT', $result['data']);
        $this->assertArrayHasKey('ETHUSDT', $result['data']);
        $this->assertArrayNotHasKey('ADAUSDT', $result['data']);

        $btcTicker = $result['data']['BTCUSDT'];
        $this->assertEquals('BTCUSDT', $btcTicker['symbol']);
        $this->assertEquals(43000.0, $btcTicker['last_price']);
    }

    public function test_get_instrument_info_success(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => [
                'list' => [
                    [
                        'symbol' => 'BTCUSDT',
                        'status' => 'Trading',
                        'baseCoin' => 'BTC',
                        'quoteCoin' => 'USDT',
                        'priceScale' => 2,
                        'lotSizeFilter' => [
                            'qtyStep' => '0.001',
                            'minOrderQty' => '0.001',
                            'maxOrderQty' => '100.0',
                        ],
                        'priceFilter' => [
                            'tickSize' => '0.1',
                            'minPrice' => '0.1',
                            'maxPrice' => '200000.0',
                        ],
                        'leverageFilter' => [
                            'minLeverage' => '1',
                            'maxLeverage' => '100',
                            'leverageStep' => '0.01',
                        ],
                    ]
                ]
            ]
        ];

        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->with('GET', 'v5/market/instruments-info', [
                            'category' => 'linear',
                            'symbol' => 'BTCUSDT'
                        ])
                        ->andReturn($mockResponse);

        $result = $this->marketData->getInstrumentInfo('BTCUSDT');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);

        $info = $result['data'];
        $this->assertEquals('BTCUSDT', $info['symbol']);
        $this->assertEquals('Trading', $info['status']);
        $this->assertEquals('BTC', $info['base_coin']);
        $this->assertEquals('USDT', $info['quote_coin']);
        $this->assertEquals(2, $info['price_scale']);
        $this->assertEquals(0.001, $info['qty_step']);
        $this->assertEquals(0.001, $info['min_order_qty']);
        $this->assertEquals(100.0, $info['max_order_qty']);
        $this->assertEquals(0.1, $info['tick_size']);
        $this->assertTrue($info['is_trading']);

        $this->assertArrayHasKey('leverage_filter', $info);
        $this->assertEquals(1.0, $info['leverage_filter']['min_leverage']);
        $this->assertEquals(100.0, $info['leverage_filter']['max_leverage']);
    }

    public function test_caching_works_correctly(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => ['list' => []]
        ];

        // First call should hit the API
        $this->mockClient->shouldReceive('publicRequest')
                        ->once()
                        ->andReturn($mockResponse);

        $result1 = $this->marketData->getKlines('BTCUSDT');
        $result2 = $this->marketData->getKlines('BTCUSDT'); // Should use cache

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
    }

    public function test_cache_keys_are_different_for_different_parameters(): void
    {
        $mockResponse = [
            'success' => true,
            'result' => ['list' => []]
        ];

        // Different parameters should result in different cache keys and API calls
        $this->mockClient->shouldReceive('publicRequest')
                        ->twice()
                        ->andReturn($mockResponse);

        $this->marketData->getKlines('BTCUSDT', '1');
        $this->marketData->getKlines('BTCUSDT', '5'); // Different interval
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
