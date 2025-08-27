<?php

declare(strict_types=1);

namespace App\Services\Exchange;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class BybitClient implements ExchangeClientInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $cfg;

    /**
     * @param  array<string, mixed>  $cfg
     */
    public function __construct(array $cfg = [])
    {
        $this->cfg = $cfg ?: (config('exchange.bybit') ?? []);
    }

    private function baseUrl(): string
    {
        $rest = $this->cfg['endpoints']['rest'] ?? [];
        $key = ! empty($this->cfg['testnet']) ? 'testnet' : 'mainnet';

        return $rest[$key] ?? 'https://api-testnet.bybit.com';
    }

    /**
     * Get server time
     *
     * @return array<string, mixed>
     */
    public function serverTime(): array
    {
        $resp = $this->makeHttpRequest('GET', '/v5/market/time', [], []);

        return $resp;
    }

    /**
     * Get wallet balance
     *
     * @return array<string, mixed>
     */
    public function getWalletBalance(string $accountType = 'UNIFIED'): array
    {
        $params = [
            'accountType' => $accountType,
        ];

        ksort($params);
        $queryString = http_build_query($params);
        $headers = $this->authHeadersForGet($queryString);

        $resp = $this->makeHttpRequest('GET', '/v5/account/wallet-balance?'.$queryString, [], $headers);

        return $resp;
    }

    /**
     * Get ticker information
     *
     * @return array<string, mixed>
     */
    public function getTicker(string $symbol): array
    {
        $params = [
            'category' => 'linear',
            'symbol' => $symbol,
        ];

        return $this->makeHttpRequest('GET', '/v5/market/tickers', $params, []);
    }

    /**
     * Get kline/candlestick data
     *
     * @return array<string, mixed>
     */
    public function getKline(string $symbol, string $interval = '1', int $limit = 1): array
    {
        $params = [
            'category' => 'linear',
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
        ];

        return $this->makeHttpRequest('GET', '/v5/market/kline', $params, []);
    }

    /**
     * KALDIRAÇ AYARLAR. BU, EKSİK OLAN VE SORUNU ÇÖZECEK OLAN METODDUR.
     */
    /**
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    public function setLeverage(string $symbol, int $leverage, array $opts = []): array
    {
        $body = [
            'category' => $opts['category'] ?? $this->cfg['category'] ?? 'linear',
            'symbol' => $symbol,
            'buyLeverage' => $this->fmt($leverage),
            'sellLeverage' => $this->fmt($leverage),
        ];

        // Ek options'ları body'e ekle
        foreach (['accountType', 'buyLeverage', 'sellLeverage'] as $key) {
            if (isset($opts[$key]) && $key !== 'buyLeverage' && $key !== 'sellLeverage') {
                $body[$key] = $opts[$key];
            }
        }
        $json = json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed');
        }
        $headers = $this->authHeaders($json);

        $resp = $this->makeHttpRequest('POST', '/v5/position/set-leverage', $body, $headers);

        return $resp;
    }

    /**
     * HTTP request with retry mechanism
     * Şartname: 1,2,4,8... saniyelik backoff ve jitter
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function makeHttpRequest(string $method, string $endpoint, array $body = [], array $headers = []): array
    {
        $maxRetries = config('exchange.retry.max_attempts', 4);
        $baseDelay = config('exchange.retry.base_delay', 1);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $http = Http::baseUrl($this->baseUrl())
                    ->withHeaders($headers)
                    ->timeout(config('exchange.timeout', 30))
                    ->retry(0, 0); // Disable default retry, use custom

                if ($method === 'GET') {
                    $response = $http->get($endpoint);
                } else {
                    $response = $http->$method($endpoint, $body);
                }

                if ($response->successful()) {
                    return $response->json();
                }

                // HTTP error, check if retryable
                if ($this->isRetryableError($response->status())) {
                    if ($attempt < $maxRetries) {
                        $delay = $this->calculateRetryDelay($attempt, $baseDelay);
                        sleep($delay);

                        continue;
                    }
                }

                // Non-retryable error or max attempts reached
                $response->throw();

            } catch (\Throwable $e) {
                if ($attempt < $maxRetries && $this->isRetryableException($e)) {
                    $delay = $this->calculateRetryDelay($attempt, $baseDelay);
                    sleep($delay);

                    continue;
                }

                // Re-throw if max attempts reached or non-retryable
                throw $e;
            }
        }

        throw new \Exception("Max retry attempts ({$maxRetries}) reached");
    }

    /**
     * Retry delay hesapla (1,2,4,8... + jitter)
     */
    private function calculateRetryDelay(int $attempt, int $baseDelay): int
    {
        $exponentialDelay = $baseDelay * pow(2, $attempt - 1);
        $jitter = rand(0, 1000) / 1000; // 0-1 saniye jitter

        return (int) ($exponentialDelay + $jitter);
    }

    /**
     * Retryable HTTP error kontrolü
     */
    private function isRetryableError(int $statusCode): bool
    {
        return in_array($statusCode, [
            408, // Request Timeout
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
        ]);
    }

    /**
     * Retryable exception kontrolü
     */
    private function isRetryableException(\Throwable $e): bool
    {
        $retryableMessages = [
            'timeout',
            'connection refused',
            'network error',
            'temporary failure',
            'rate limit',
            'too many requests',
        ];

        $message = strtolower($e->getMessage());
        foreach ($retryableMessages as $retryable) {
            if (str_contains($message, $retryable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    public function createOrder(
        string $symbol,
        string $side,
        string $type,
        float $qty,
        ?float $price = null,
        array $opts = []
    ): array {

        // Bybit API v5 side format: "Buy" or "Sell" (case sensitive)
        $sideUp = strtoupper($side);
        if ($sideUp === 'LONG' || $sideUp === 'BUY') {
            $sideUp = 'Buy';
        } elseif ($sideUp === 'SHORT' || $sideUp === 'SELL') {
            $sideUp = 'Sell';
        } else {
            // Keep original case if already correct
            $sideUp = $side;
        }

        // Idempotency key oluştur
        $idempotencyKey = $this->generateIdempotencyKey(
            $symbol,
            $side,
            $type,
            $qty,
            $price,
            $opts
        );

        $body = [
            'category' => $this->cfg['category'] ?? 'linear',
            'symbol' => $symbol,
            'side' => $sideUp,
            'orderType' => strtoupper($type),
            'qty' => $this->fmt($qty),
            'timeInForce' => $opts['timeInForce'] ?? ($type === 'LIMIT' ? 'PostOnly' : 'IOC'),
            'reduceOnly' => (bool) ($opts['reduceOnly'] ?? false),
            'orderLinkId' => $opts['orderLinkId'] ?? $idempotencyKey,
        ];
        if ($price !== null) {
            $body['price'] = $this->fmt($price);
        }
        foreach (['takeProfit', 'stopLoss', 'tpslMode'] as $k) {
            if (array_key_exists($k, $opts) && $opts[$k] !== null) {
                $body[$k] = is_numeric($opts[$k])
                    ? $this->fmt((float) $opts[$k])
                    : (string) $opts[$k];
            }
        }

        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed');
        }
        $headers = $this->authHeaders($json);

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($headers)
                ->post('/v5/order/create', $body)
                ->throw()
                ->json();

            // Standart başarı formatı
            return [
                'ok' => true,
                'result' => $resp,
                'orderId' => $resp['result']['orderId'] ?? null,
                'idempotencyKey' => $idempotencyKey,
            ];
        } catch (\Throwable $e) {
            // Standart hata formatı
            $errorCode = 'ORDER_CREATE_FAILED';
            if (str_contains($e->getMessage(), 'PostOnly')) {
                $errorCode = 'POST_ONLY_REJECT';
            }

            return [
                'ok' => false,
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
                'idempotencyKey' => $idempotencyKey,
            ];
        }
    }

    /**
     * OCO (One Cancels Other) order oluştur
     *
     * @param  array<string, mixed>  $params  OCO parametreleri
     * @return array<string, mixed> ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function createOcoOrder(array $params): array
    {
        try {
            $json = json_encode($params, JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new \RuntimeException('JSON encoding failed for OCO order');
            }
            $headers = $this->authHeaders($json);

            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($headers)
                ->post('/v5/order/create', $params)
                ->throw()
                ->json();

            return [
                'ok' => true,
                'result' => $resp,
                'error_message' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'result' => null,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * OCO order iptal et
     *
     * @param  array<string, mixed>  $params  İptal parametreleri
     * @return array<string, mixed> ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function cancelOcoOrder(array $params): array
    {
        try {
            $json = json_encode($params, JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new \RuntimeException('JSON encoding failed for cancel OCO order');
            }
            $headers = $this->authHeaders($json);

            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($headers)
                ->post('/v5/order/cancel', $params)
                ->throw()
                ->json();

            return [
                'ok' => true,
                'result' => $resp,
                'error_message' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'result' => null,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * OCO order bilgisi al
     *
     * @param  array  $params  Sorgu parametreleri
     * @return array ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function getOcoOrder(array $params): array
    {
        try {
            $json = json_encode($params, JSON_UNESCAPED_SLASHES);
            $headers = $this->authHeaders($json);

            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($headers)
                ->get('/v5/order/realtime', $params)
                ->throw()
                ->json();

            return [
                'ok' => true,
                'result' => $resp,
                'error_message' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'result' => null,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Idempotency key oluştur
     * Aynı parametrelerle tekrar çağrıldığında aynı key üretir
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  string  $type  Order tipi
     * @param  float  $qty  Miktar
     * @param  float|null  $price  Fiyat
     * @param  array  $opts  Ek options
     * @return string Idempotency key
     */
    private function generateIdempotencyKey(
        string $symbol,
        string $side,
        string $type,
        float $qty,
        ?float $price,
        array $opts
    ): string {

        // Idempotency için kullanılacak parametreler
        $idempotencyParams = [
            'symbol' => strtoupper($symbol),
            'side' => strtoupper($side),
            'type' => strtoupper($type),
            'qty' => $this->fmt($qty),
            'price' => $price !== null ? $this->fmt($price) : 'null',
            'category' => $opts['category'] ?? $this->cfg['category'] ?? 'linear',
            'timeInForce' => $opts['timeInForce'] ?? ($type === 'LIMIT' ? 'PostOnly' : 'IOC'),
            'reduceOnly' => (bool) ($opts['reduceOnly'] ?? false),
        ];

        // Take profit ve stop loss da idempotency'ye dahil et
        if (isset($opts['takeProfit'])) {
            $idempotencyParams['takeProfit'] = is_numeric($opts['takeProfit'])
                ? $this->fmt((float) $opts['takeProfit'])
                : (string) $opts['takeProfit'];
        }
        if (isset($opts['stopLoss'])) {
            $idempotencyParams['stopLoss'] = is_numeric($opts['stopLoss'])
                ? $this->fmt((float) $opts['stopLoss'])
                : (string) $opts['stopLoss'];
        }

        // Parametreleri sırala (tutarlılık için)
        ksort($idempotencyParams);

        // JSON encode ve hash
        $jsonString = json_encode(
            $idempotencyParams,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $hash = hash('sha256', $jsonString);

        // Kısa ve okunabilir key oluştur
        return substr($hash, 0, 16);
    }

    /**
     * Idempotency key ile order'ı kontrol et
     * Aynı key ile daha önce order oluşturulmuş mu?
     *
     * @param  string  $idempotencyKey  Idempotency key
     * @return array ['exists' => bool, 'orderId' => string|null, 'details' => array]
     */
    public function checkIdempotency(string $idempotencyKey): array
    {
        try {
            $body = [
                'category' => $this->cfg['category'] ?? 'linear',
                'orderLinkId' => $idempotencyKey,
            ];

            $json = json_encode($body);
            $headers = $this->authHeaders($json);

            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($headers)
                ->get('/v5/order/realtime', $body)
                ->throw()
                ->json();

            if (isset($resp['result']['list']) && ! empty($resp['result']['list'])) {
                $order = $resp['result']['list'][0];

                return [
                    'exists' => true,
                    'orderId' => $order['orderId'] ?? null,
                    'status' => $order['orderStatus'] ?? null,
                    'details' => $order,
                ];
            }

            return [
                'exists' => false,
                'orderId' => null,
                'details' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exists' => false,
                'orderId' => null,
                'error' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    public function kline(string $symbol, string $interval = '5', int $limit = 50, ?string $category = null): array
    {
        $query = [
            'category' => $category ?? ($this->cfg['category'] ?? 'linear'),
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
        ];
        $resp = Http::baseUrl($this->baseUrl())
            ->get('/v5/market/kline', $query)
            ->throw()
            ->json();

        return is_array($resp) ? $resp : [];
    }

    public function tickers(string $symbol, ?string $category = null): array
    {
        $category = $category ?? ($this->cfg['category'] ?? 'linear');
        $resp = Http::baseUrl($this->baseUrl())
            ->get('/v5/market/tickers', [
                'category' => $category,
                'symbol' => $symbol,
            ])
            ->throw()
            ->json();

        // Testnet fiyat düzeltmesi uygula
        if (is_array($resp) && isset($resp['result']['list'])) {
            $resp = $this->correctTestnetPrices($resp);
        }

        return is_array($resp) ? $resp : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getInstrumentInfo(string $symbol): ?array
    {
        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->get('/v5/market/instruments-info', [
                    'category' => $this->cfg['category'] ?? 'linear',
                    'symbol' => $symbol,
                ])
                ->throw()
                ->json();

            if (is_array($resp) && isset($resp['result']['list'][0])) {
                $instrument = $resp['result']['list'][0];

                return [
                    'tickSize' => (float) ($instrument['tickSize'] ?? 0.1),
                    'minPrice' => (float) ($instrument['minPrice'] ?? 0.0),
                    'maxPrice' => (float) ($instrument['maxPrice'] ?? 999999.0),
                    'lotSize' => (float) ($instrument['lotSize'] ?? 0.001),
                    'minQty' => (float) ($instrument['minQty'] ?? 0.001),
                    'maxQty' => (float) ($instrument['maxQty'] ?? 999999.0),
                ];
            }
        } catch (\Throwable $e) {
            // Log error but don't fail the health check
            \Log::warning('Failed to get instrument info', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 8, '.', ''), '0'), '.') ?: '0';
    }

    /**
     * Get execution history for PnL calculation
     */
    public function executionList(string $symbol, int $startTime, int $endTime, ?string $category = null): array
    {
        try {
            $query = [
                'category' => $category ?? ($this->cfg['category'] ?? 'linear'),
                'symbol' => $symbol,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'limit' => 100,
            ];

            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders($this->authHeaders(''))
                ->get('/v5/execution/list', $query)
                ->throw()
                ->json();

            return is_array($resp) ? $resp : [];
        } catch (\Throwable $e) {
            \Log::warning('Failed to get execution list', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return [
                'result' => [
                    'list' => [],
                ],
            ];
        }
    }

    /**
     * Hesap bilgilerini al
     *
     * @return array<string, mixed>
     */
    public function getAccountInfo(): array
    {
        $params = [
            'accountType' => $this->cfg['account_type'] ?? 'UNIFIED',
        ];

        // Bybit API v5 için parametreleri alfabetik sırala
        ksort($params);
        $queryString = http_build_query($params);

        // GET istekleri için query string ile imza oluştur
        $headers = $this->authHeadersForGet($queryString);

        $resp = $this->makeHttpRequest('GET', '/v5/account/wallet-balance?'.$queryString, [], $headers);

        return $resp;
    }

    /**
     * Pozisyon bilgilerini al
     *
     * @param  string|null  $symbol  Belirli bir sembol için, null ise tüm pozisyonlar
     * @return array<string, mixed>
     */
    public function getPositions(?string $symbol = null): array
    {
        $params = [
            'category' => 'linear',
        ];

        if ($symbol !== null) {
            $params['symbol'] = $symbol;
        } else {
            // Tüm pozisyonlar için settleCoin parametresi ekle
            $params['settleCoin'] = 'USDT';
        }

        // Bybit API v5 için parametreleri alfabetik sırala
        ksort($params);
        $queryString = http_build_query($params);

        // GET istekleri için query string ile imza oluştur
        $headers = $this->authHeadersForGet($queryString);

        $resp = $this->makeHttpRequest('GET', '/v5/position/list?'.$queryString, [], $headers);

        // Pozisyon fiyat düzeltmesi uygula
        if (is_array($resp) && isset($resp['result']['list'])) {
            $resp = $this->correctTestnetPositionPrices($resp);
        }

        return $resp;
    }

    /**
     * Pozisyonu kapat
     *
     * @param  string  $side  Buy/Sell
     * @return array<string, mixed>
     */
    public function closePosition(string $symbol, string $side, float $qty): array
    {
        $body = [
            'category' => $this->cfg['category'] ?? 'linear',
            'symbol' => $symbol,
            'side' => $side,
            'qty' => $this->fmt($qty),
            'orderType' => 'Market',
            'timeInForce' => 'IOC',
            'reduceOnly' => true,
        ];

        $json = json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed');
        }
        $headers = $this->authHeaders($json);

        $resp = $this->makeHttpRequest('POST', '/v5/order/create', $body, $headers);

        return $resp;
    }

    /**
     * Stop Loss ve Take Profit ayarla
     *
     * @return array<string, mixed>
     */
    public function setStopLossTakeProfit(string $symbol, ?float $stopLoss = null, ?float $takeProfit = null): array
    {
        $body = [
            'category' => $this->cfg['category'] ?? 'linear',
            'symbol' => $symbol,
        ];

        if ($stopLoss !== null) {
            $body['stopLoss'] = $this->fmt($stopLoss);
        }

        if ($takeProfit !== null) {
            $body['takeProfit'] = $this->fmt($takeProfit);
        }

        $json = json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed');
        }
        $headers = $this->authHeaders($json);

        $resp = $this->makeHttpRequest('POST', '/v5/position/trading-stop', $body, $headers);

        return $resp;
    }

    private function authHeaders(string $jsonBody): array
    {
        $ts = (string) (int) (microtime(true) * 1000);
        $recv = (string) ($this->cfg['recv_window'] ?? 15000);
        $apiKey = (string) ($this->cfg['api_key'] ?? '');
        $secret = (string) ($this->cfg['api_secret'] ?? '');
        $sign = hash_hmac('sha256', $ts.$apiKey.$recv.$jsonBody, $secret);

        return [
            'X-BAPI-API-KEY' => $apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-SIGN-TYPE' => '2',
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recv,
            'Content-Type' => 'application/json',
        ];
    }

    private function authHeadersForGet(string $queryString): array
    {
        $ts = (string) (int) (microtime(true) * 1000);
        $recv = (string) ($this->cfg['recv_window'] ?? 15000);
        $apiKey = (string) ($this->cfg['api_key'] ?? '');
        $secret = (string) ($this->cfg['api_secret'] ?? '');

        // Bybit API v5 GET istekleri için: timestamp + api_key + recv_window + query_string
        // Hata mesajından anlaşıldığı üzere: origin_string[timestamp+api_key+recv_window+query_string]
        $signString = $ts.$apiKey.$recv.$queryString;
        $sign = hash_hmac('sha256', $signString, $secret);

        return [
            'X-BAPI-API-KEY' => $apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-SIGN-TYPE' => '2',
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recv,
        ];
    }

    /**
     * Close position with reduce-only market order
     *
     * @return array<string, mixed>
     */
    public function closeReduceOnlyMarket(string $symbol, string $side, string $qty, string $orderLinkId): array
    {
        $body = [
            'category' => $this->cfg['category'] ?? 'linear',
            'symbol' => $symbol,
            'side' => $side,
            'orderType' => 'Market',
            'qty' => $qty,
            'orderLinkId' => $orderLinkId,
            'reduceOnly' => true,
        ];

        $json = json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed');
        }

        $headers = $this->authHeaders($json);
        $url = $this->baseUrl().'/v5/order/create';

        try {
            $response = Http::withHeaders($headers)->post($url, $body);
            $data = $response->json();

            return [
                'ok' => ($data['retCode'] ?? -1) === 0,
                'result' => $data['result'] ?? [],
                'retCode' => $data['retCode'] ?? -1,
                'retMsg' => $data['retMsg'] ?? 'Unknown error',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'result' => [],
                'retCode' => -1,
                'retMsg' => $e->getMessage(),
            ];
        }
    }

    /**
     * Testnet fiyat düzeltmesi - indexPrice gerçek piyasa fiyatını gösteriyor
     */
    private function correctTestnetPrices(array $response): array
    {
        if (isset($response['result']['list']) && is_array($response['result']['list'])) {
            foreach ($response['result']['list'] as &$ticker) {
                if (isset($ticker['indexPrice']) && $ticker['indexPrice'] > 0) {
                    $indexPrice = (float) $ticker['indexPrice'];
                    $ticker['lastPrice'] = (string) $indexPrice;
                    $ticker['markPrice'] = (string) $indexPrice;
                    $ticker['bid1Price'] = (string) ($indexPrice * 0.9995);
                    $ticker['ask1Price'] = (string) ($indexPrice * 1.0005);

                    if (isset($ticker['prevPrice24h']) && $ticker['prevPrice24h'] > 0) {
                        $prevPrice = (float) $ticker['prevPrice24h'];
                        if ($prevPrice > $indexPrice * 1.5) {
                            $ticker['prevPrice24h'] = (string) ($indexPrice * 0.98);
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Pozisyon fiyatlarını düzelt
     */
    private function correctTestnetPositionPrices(array $response): array
    {
        if (isset($response['result']['list']) && is_array($response['result']['list'])) {
            foreach ($response['result']['list'] as &$position) {
                $symbol = $position['symbol'] ?? '';

                if ($symbol && isset($position['markPrice']) && $position['markPrice'] > 0) {
                    try {
                        $params = ['category' => 'linear', 'symbol' => $symbol];
                        $tickerResp = $this->makeHttpRequest('GET', '/v5/market/tickers?'.http_build_query($params));

                        if (isset($tickerResp['result']['list'][0]['indexPrice'])) {
                            $indexPrice = (float) $tickerResp['result']['list'][0]['indexPrice'];

                            if ($indexPrice > 0) {
                                $position['markPrice'] = (string) $indexPrice;

                                if (isset($position['avgPrice']) && $position['avgPrice'] > 0) {
                                    $avgPrice = (float) $position['avgPrice'];
                                    if ($avgPrice > $indexPrice * 1.5) {
                                        $position['avgPrice'] = (string) $indexPrice;
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Position price correction failed', ['symbol' => $symbol]);
                    }
                }
            }
        }

        return $response;
    }
}
