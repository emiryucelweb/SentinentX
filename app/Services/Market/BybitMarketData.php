<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Services\Exchange\BybitClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bybit Market Data Service
 * Production-ready implementation for market data retrieval
 */
class BybitMarketData
{
    public function __construct(
        private readonly BybitClient $client
    ) {}

    /**
     * Get kline/candlestick data
     *
     * @param string $symbol
     * @param string $interval (1|3|5|15|30|60|120|240|360|720|D|M|W)
     * @param int $limit
     * @param int|null $startTime
     * @param int|null $endTime
     * @return array<string, mixed>
     */
    public function getKlines(
        string $symbol,
        string $interval = '1',
        int $limit = 200,
        ?int $startTime = null,
        ?int $endTime = null
    ): array {
        $cacheKey = "klines_{$symbol}_{$interval}_{$limit}";
        
        if ($startTime || $endTime) {
            $cacheKey .= "_{$startTime}_{$endTime}";
        }

        return Cache::remember($cacheKey, 30, function () use ($symbol, $interval, $limit, $startTime, $endTime) {
            try {
                $params = [
                    'category' => 'linear',
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => min($limit, 1000), // API limit
                ];

                if ($startTime) {
                    $params['start'] = $startTime * 1000; // Convert to ms
                }

                if ($endTime) {
                    $params['end'] = $endTime * 1000; // Convert to ms
                }

                $response = $this->client->publicRequest('GET', 'v5/market/kline', $params);

                if (!$response['success'] || !isset($response['result']['list'])) {
                    Log::warning('Failed to fetch klines', [
                        'symbol' => $symbol,
                        'response' => $response
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => [],
                        'error' => 'Failed to fetch klines'
                    ];
                }

                // Transform to standardized format
                $klines = array_map(function ($kline) {
                    return [
                        'timestamp' => (int) ($kline[0] / 1000), // Convert from ms
                        'open' => (float) $kline[1],
                        'high' => (float) $kline[2],
                        'low' => (float) $kline[3],
                        'close' => (float) $kline[4],
                        'volume' => (float) $kline[5],
                        'turnover' => (float) $kline[6],
                    ];
                }, $response['result']['list']);

                return [
                    'success' => true,
                    'data' => $klines,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'count' => count($klines)
                ];

            } catch (\Exception $e) {
                Log::error('Klines API error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get current ticker information
     *
     * @param string $symbol
     * @return array<string, mixed>
     */
    public function getTicker(string $symbol): array
    {
        $cacheKey = "ticker_{$symbol}";

        return Cache::remember($cacheKey, 10, function () use ($symbol) {
            try {
                $response = $this->client->publicRequest('GET', 'v5/market/tickers', [
                    'category' => 'linear',
                    'symbol' => $symbol
                ]);

                if (!$response['success'] || !isset($response['result']['list'][0])) {
                    Log::warning('Failed to fetch ticker', [
                        'symbol' => $symbol,
                        'response' => $response
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Failed to fetch ticker'
                    ];
                }

                $ticker = $response['result']['list'][0];

                return [
                    'success' => true,
                    'data' => [
                        'symbol' => $ticker['symbol'],
                        'last_price' => (float) $ticker['lastPrice'],
                        'bid' => (float) $ticker['bid1Price'],
                        'ask' => (float) $ticker['ask1Price'],
                        'volume_24h' => (float) $ticker['volume24h'],
                        'turnover_24h' => (float) $ticker['turnover24h'],
                        'price_change_24h' => (float) $ticker['price24hPcnt'],
                        'high_24h' => (float) $ticker['highPrice24h'],
                        'low_24h' => (float) $ticker['lowPrice24h'],
                        'timestamp' => time(),
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Ticker API error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get orderbook data
     *
     * @param string $symbol
     * @param int $limit
     * @return array<string, mixed>
     */
    public function getOrderbook(string $symbol, int $limit = 25): array
    {
        $cacheKey = "orderbook_{$symbol}_{$limit}";

        return Cache::remember($cacheKey, 5, function () use ($symbol, $limit) {
            try {
                $response = $this->client->publicRequest('GET', 'v5/market/orderbook', [
                    'category' => 'linear',
                    'symbol' => $symbol,
                    'limit' => min($limit, 500) // API limit
                ]);

                if (!$response['success'] || !isset($response['result'])) {
                    Log::warning('Failed to fetch orderbook', [
                        'symbol' => $symbol,
                        'response' => $response
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Failed to fetch orderbook'
                    ];
                }

                $result = $response['result'];

                return [
                    'success' => true,
                    'data' => [
                        'symbol' => $result['s'],
                        'timestamp' => (int) ($result['ts'] / 1000),
                        'bids' => array_map(function ($bid) {
                            return [(float) $bid[0], (float) $bid[1]];
                        }, $result['b']),
                        'asks' => array_map(function ($ask) {
                            return [(float) $ask[0], (float) $ask[1]];
                        }, $result['a']),
                        'update_id' => (int) $result['u'],
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Orderbook API error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get multiple symbols ticker data
     *
     * @param array<string> $symbols
     * @return array<string, mixed>
     */
    public function getMultipleTickers(array $symbols): array
    {
        $cacheKey = 'tickers_' . md5(implode(',', $symbols));

        return Cache::remember($cacheKey, 15, function () use ($symbols) {
            try {
                $response = $this->client->publicRequest('GET', 'v5/market/tickers', [
                    'category' => 'linear'
                ]);

                if (!$response['success'] || !isset($response['result']['list'])) {
                    return [
                        'success' => false,
                        'data' => [],
                        'error' => 'Failed to fetch tickers'
                    ];
                }

                $tickers = [];
                foreach ($response['result']['list'] as $ticker) {
                    if (in_array($ticker['symbol'], $symbols)) {
                        $tickers[$ticker['symbol']] = [
                            'symbol' => $ticker['symbol'],
                            'last_price' => (float) $ticker['lastPrice'],
                            'bid' => (float) $ticker['bid1Price'],
                            'ask' => (float) $ticker['ask1Price'],
                            'volume_24h' => (float) $ticker['volume24h'],
                            'price_change_24h' => (float) $ticker['price24hPcnt'],
                        ];
                    }
                }

                return [
                    'success' => true,
                    'data' => $tickers,
                    'count' => count($tickers)
                ];

            } catch (\Exception $e) {
                Log::error('Multiple tickers API error', [
                    'symbols' => $symbols,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get symbol information
     *
     * @param string $symbol
     * @return array<string, mixed>
     */
    public function getInstrumentInfo(string $symbol): array
    {
        $cacheKey = "instrument_info_{$symbol}";

        return Cache::remember($cacheKey, 3600, function () use ($symbol) { // Cache for 1 hour
            try {
                $response = $this->client->publicRequest('GET', 'v5/market/instruments-info', [
                    'category' => 'linear',
                    'symbol' => $symbol
                ]);

                if (!$response['success'] || !isset($response['result']['list'][0])) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Failed to fetch instrument info'
                    ];
                }

                $info = $response['result']['list'][0];

                return [
                    'success' => true,
                    'data' => [
                        'symbol' => $info['symbol'],
                        'status' => $info['status'],
                        'base_coin' => $info['baseCoin'],
                        'quote_coin' => $info['quoteCoin'],
                        'price_scale' => (int) $info['priceScale'],
                        'qty_step' => (float) $info['lotSizeFilter']['qtyStep'],
                        'min_order_qty' => (float) $info['lotSizeFilter']['minOrderQty'],
                        'max_order_qty' => (float) $info['lotSizeFilter']['maxOrderQty'],
                        'tick_size' => (float) $info['priceFilter']['tickSize'],
                        'min_price' => (float) $info['priceFilter']['minPrice'],
                        'max_price' => (float) $info['priceFilter']['maxPrice'],
                        'is_trading' => $info['status'] === 'Trading',
                        'leverage_filter' => [
                            'min_leverage' => (float) $info['leverageFilter']['minLeverage'],
                            'max_leverage' => (float) $info['leverageFilter']['maxLeverage'],
                            'leverage_step' => (float) $info['leverageFilter']['leverageStep'],
                        ]
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Instrument info API error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $e->getMessage()
                ];
            }
        });
    }
}