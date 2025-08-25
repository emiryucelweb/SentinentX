<?php

declare(strict_types=1);

namespace App\Services\Health;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Services\Notifier\AlertDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class StablecoinHealthCheck
{
    private const CACHE_KEY = 'stablecoin_health_check';

    private const CACHE_TTL = 300; // 5 dakika

    public function __construct(
        private readonly ExchangeClientInterface $exchange,
        private readonly AlertDispatcher $alerts
    ) {}

    /**
     * Stablecoin sağlık kontrolü
     */
    public function check(): array
    {
        try {
            $result = $this->performHealthCheck();

            // Sonucu cache'le
            Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

            // Alert gönder (gerekirse)
            $this->sendAlerts($result);

            return $result;

        } catch (\Throwable $e) {
            Log::error('Stablecoin health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage(),
                'stablecoins' => [],
            ];
        }
    }

    /**
     * Sağlık kontrolü yap
     */
    private function performHealthCheck(): array
    {
        $stablecoins = config('health.stablecoin.monitored', [
            'USDT' => ['symbol' => 'USDTUSDT', 'threshold' => 0.98],
            'USDC' => ['symbol' => 'USDCUSDT', 'threshold' => 0.98],
            'BUSD' => ['symbol' => 'BUSDUSDT', 'threshold' => 0.98],
            'DAI' => ['symbol' => 'DAIUSDT', 'threshold' => 0.98],
        ]);

        $results = [];
        $overallStatus = 'healthy';

        foreach ($stablecoins as $coin => $config) {
            $checkResult = $this->checkStablecoin($coin, $config);
            $results[$coin] = $checkResult;

            if ($checkResult['status'] === 'depegged') {
                $overallStatus = 'depegged';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'stablecoins' => $results,
            'summary' => $this->generateSummary($results),
        ];
    }

    /**
     * Tek stablecoin kontrolü
     */
    private function checkStablecoin(string $coin, array $config): array
    {
        try {
            $symbol = $config['symbol'];
            $threshold = $config['threshold'];

            // Fiyat bilgisini al
            $priceData = $this->exchange->tickers($symbol);
            $price = $this->extractPrice($priceData);

            if ($price === null) {
                return [
                    'status' => 'unknown',
                    'price' => null,
                    'threshold' => $threshold,
                    'deviation' => null,
                    'last_check' => now()->toISOString(),
                    'error' => 'Price data unavailable',
                ];
            }

            // Depeg kontrolü
            $deviation = abs(1.0 - $price);
            $isDepegged = $deviation > (1.0 - $threshold);

            $status = $isDepegged ? 'depegged' : 'healthy';

            return [
                'status' => $status,
                'price' => $price,
                'threshold' => $threshold,
                'deviation' => $deviation,
                'deviation_pct' => round($deviation * 100, 4),
                'last_check' => now()->toISOString(),
                'is_depegged' => $isDepegged,
            ];

        } catch (\Throwable $e) {
            Log::warning("Stablecoin check failed for {$coin}", [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);

            return [
                'status' => 'error',
                'price' => null,
                'threshold' => $config['threshold'] ?? 0.98,
                'deviation' => null,
                'last_check' => now()->toISOString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fiyat bilgisini çıkar
     */
    private function extractPrice(array $priceData): ?float
    {
        try {
            // Bybit format
            if (isset($priceData['result']['list'][0]['lastPrice'])) {
                return (float) $priceData['result']['list'][0]['lastPrice'];
            }

            // Alternatif format
            if (isset($priceData['result']['list'][0]['price'])) {
                return (float) $priceData['result']['list'][0]['price'];
            }

            // Raw price
            if (isset($priceData['price'])) {
                return (float) $priceData['price'];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Price extraction failed', [
                'data' => $priceData,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Özet oluştur
     */
    private function generateSummary(array $results): array
    {
        $total = count($results);
        $healthy = 0;
        $depegged = 0;
        $unknown = 0;
        $errors = 0;

        foreach ($results as $result) {
            switch ($result['status']) {
                case 'healthy':
                    $healthy++;
                    break;
                case 'depegged':
                    $depegged++;
                    break;
                case 'unknown':
                    $unknown++;
                    break;
                case 'error':
                    $errors++;
                    break;
            }
        }

        return [
            'total' => $total,
            'healthy' => $healthy,
            'depegged' => $depegged,
            'unknown' => $unknown,
            'errors' => $errors,
            'health_percentage' => $total > 0 ? round(($healthy / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Alert gönder
     */
    private function sendAlerts(array $result): void
    {
        if ($result['status'] === 'depegged') {
            $depeggedCoins = [];
            foreach ($result['stablecoins'] as $coin => $data) {
                if ($data['status'] === 'depegged') {
                    $depeggedCoins[] = [
                        'coin' => $coin,
                        'price' => $data['price'],
                        'deviation_pct' => $data['deviation_pct'] ?? 'N/A',
                    ];
                }
            }

            $this->alerts->send(
                'critical',
                'STABLECOIN_DEPEG_DETECTED',
                'Stablecoin depeg detected - immediate action required',
                [
                    'depegged_coins' => $depeggedCoins,
                    'timestamp' => $result['timestamp'],
                    'summary' => $result['summary'],
                ],
                dedupKey: 'stablecoin-depeg-'.date('Y-m-d-H')
            );
        }

        // Genel sağlık durumu alert'i
        if ($result['summary']['health_percentage'] < 75) {
            $this->alerts->send(
                'warn',
                'STABLECOIN_HEALTH_DEGRADED',
                'Stablecoin health degraded - monitoring required',
                [
                    'health_percentage' => $result['summary']['health_percentage'],
                    'timestamp' => $result['timestamp'],
                    'summary' => $result['summary'],
                ],
                dedupKey: 'stablecoin-health-'.date('Y-m-d-H')
            );
        }
    }

    /**
     * Son kontrol sonucunu al
     */
    public function getLastCheck(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Manuel kontrol tetikle
     */
    public function triggerCheck(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->check();
    }

    /**
     * Belirli stablecoin kontrolü
     */
    public function checkSpecific(string $coin): ?array
    {
        $stablecoins = config('health.stablecoin.monitored', []);

        if (! isset($stablecoins[$coin])) {
            return null;
        }

        $result = $this->checkStablecoin($coin, $stablecoins[$coin]);

        // Alert gönder (gerekirse)
        if ($result['status'] === 'depegged') {
            $this->alerts->send(
                'critical',
                'STABLECOIN_SPECIFIC_DEPEG',
                "Stablecoin {$coin} depeg detected",
                [
                    'coin' => $coin,
                    'result' => $result,
                    'timestamp' => now()->toISOString(),
                ]
            );
        }

        return $result;
    }
}
