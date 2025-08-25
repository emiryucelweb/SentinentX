<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Support\Facades\Log;

final class OcoService
{
    public function __construct(
        private ExchangeClientInterface $exchange,
        private int $maxRetries = 3,
        private int $retryDelayMs = 100 // 100ms default (eski: 2000ms)
    ) {}

    /**
     * OCO (One Cancels Other) order kur
     * TP/SL: OCO, reduceOnly, trigger=MARK_PRICE
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  float  $qty  Miktar
     * @param  float  $takeProfit  Take profit fiyatı
     * @param  float  $stopLoss  Stop loss fiyatı
     * @param  array  $opts  Ek options
     * @return array ['ok' => bool, 'oco_id' => string|null, 'details' => array]
     */
    public function setupOco(
        string $symbol,
        string $side,
        float $qty,
        float $takeProfit,
        float $stopLoss,
        array $opts = []
    ): array {
        $side = strtoupper($side);
        $exchangeSide = $side === 'LONG' ? 'Buy' : 'Sell';

        // OCO order parametreleri
        $ocoParams = [
            'category' => $opts['category'] ?? 'linear',
            'symbol' => $symbol,
            'side' => $exchangeSide,
            'qty' => $qty,
            'takeProfit' => $takeProfit,
            'stopLoss' => $stopLoss,
            'tpslMode' => 'Partial',
            'tpOrderType' => 'Limit',
            'slOrderType' => 'Market',
            'tpTriggerBy' => 'MarkPrice',
            'slTriggerBy' => 'MarkPrice',
            'reduceOnly' => true,
            'timeInForce' => 'GTC',
        ];

        // Ek options'ları ekle
        foreach (['orderLinkId', 'tpLimitPrice', 'slLimitPrice'] as $key) {
            if (isset($opts[$key])) {
                $ocoParams[$key] = $opts[$key];
            }
        }

        try {
            $response = $this->exchange->createOcoOrder($ocoParams);

            if ($response['ok'] ?? false) {
                return [
                    'ok' => true,
                    'oco_id' => $response['result']['ocoId'] ?? null,
                    'details' => $response['result'] ?? [],
                    'attempt' => 1,
                ];
            }

            return [
                'ok' => false,
                'oco_id' => null,
                'error' => $response['error_message'] ?? 'Unknown error',
                'details' => $response,
                'attempt' => 1,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'oco_id' => null,
                'error' => $e->getMessage(),
                'details' => [],
                'attempt' => 1,
            ];
        }
    }

    /**
     * OCO kurulum retry sistemi
     * Pozitif onay alana kadar retry
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  float  $qty  Miktar
     * @param  float  $takeProfit  Take profit fiyatı
     * @param  float  $stopLoss  Stop loss fiyatı
     * @param  array  $opts  Ek options
     * @return array ['ok' => bool, 'oco_id' => string|null, 'attempts' => int, 'details' => array]
     */
    public function setupOcoWithRetry(
        string $symbol,
        string $side,
        float $qty,
        float $takeProfit,
        float $stopLoss,
        array $opts = []
    ): array {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->maxRetries) {
            $attempts++;

            Log::info('OCO setup attempt', [
                'symbol' => $symbol,
                'side' => $side,
                'attempt' => $attempts,
                'max_retries' => $this->maxRetries,
            ]);

            $result = $this->setupOco($symbol, $side, $qty, $takeProfit, $stopLoss, $opts);

            if ($result['ok']) {
                Log::info('OCO setup successful', [
                    'symbol' => $symbol,
                    'oco_id' => $result['oco_id'],
                    'attempts' => $attempts,
                ]);

                return [
                    'ok' => true,
                    'oco_id' => $result['oco_id'],
                    'attempts' => $attempts,
                    'details' => $result['details'],
                ];
            }

            $lastError = $result['error'];
            Log::warning('OCO setup failed', [
                'symbol' => $symbol,
                'attempt' => $attempts,
                'error' => $lastError,
            ]);

            // Son deneme değilse bekle (optimize edilmiş delay)
            if ($attempts < $this->maxRetries) {
                $this->smartDelay($attempts);
            }
        }

        Log::error('OCO setup failed after all retries', [
            'symbol' => $symbol,
            'side' => $side,
            'attempts' => $attempts,
            'last_error' => $lastError,
        ]);

        return [
            'ok' => false,
            'oco_id' => null,
            'attempts' => $attempts,
            'error' => $lastError,
            'details' => [],
        ];
    }

    /**
     * Akıllı delay sistemi: Linear backoff + jitter
     *
     * @param  int  $attempt  Mevcut deneme sayısı
     */
    private function smartDelay(int $attempt): void
    {
        // Linear backoff: 100ms, 200ms, 300ms (eski: 2s, 2s, 2s)
        $baseDelay = $this->retryDelayMs * $attempt;

        // Jitter ekle (±20% random)
        $jitter = $baseDelay * 0.2 * (mt_rand(-100, 100) / 100);
        $finalDelay = max(10, $baseDelay + $jitter); // Minimum 10ms

        // Test ortamında çok kısa, production'da normal
        if (app()->environment('testing')) {
            usleep((int) ($finalDelay * 1000)); // Microseconds
        } else {
            usleep((int) ($finalDelay * 1000)); // Production'da da hızlı
        }
    }

    /**
     * OCO order'ı iptal et
     *
     * @param  string  $symbol  Sembol
     * @param  string  $ocoId  OCO ID
     * @return array ['ok' => bool, 'details' => array]
     */
    public function cancelOco(string $symbol, string $ocoId): array
    {
        try {
            $params = [
                'category' => 'linear',
                'symbol' => $symbol,
                'ocoId' => $ocoId,
            ];

            $response = $this->exchange->cancelOcoOrder($params);

            return [
                'ok' => $response['ok'] ?? false,
                'details' => $response['result'] ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * OCO order durumunu kontrol et
     *
     * @param  string  $symbol  Sembol
     * @param  string  $ocoId  OCO ID
     * @return array ['ok' => bool, 'status' => string, 'details' => array]
     */
    public function checkOcoStatus(string $symbol, string $ocoId): array
    {
        try {
            $params = [
                'category' => 'linear',
                'symbol' => $symbol,
                'ocoId' => $ocoId,
            ];

            $response = $this->exchange->getOcoOrder($params);

            if ($response['ok'] ?? false) {
                $order = $response['result'] ?? [];

                return [
                    'ok' => true,
                    'status' => $order['orderStatus'] ?? 'UNKNOWN',
                    'details' => $order,
                ];
            }

            return [
                'ok' => false,
                'status' => 'UNKNOWN',
                'error' => $response['error_message'] ?? 'Unknown error',
                'details' => $response,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'UNKNOWN',
                'error' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * OCO relink (yeniden kurulum)
     * Pozisyon kapanması sonrası yeni OCO kur
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  float  $qty  Miktar
     * @param  float  $takeProfit  Take profit fiyatı
     * @param  float  $stopLoss  Stop loss fiyatı
     * @param  array  $opts  Ek options
     * @return array ['ok' => bool, 'oco_id' => string|null, 'details' => array]
     */
    public function relinkOco(
        string $symbol,
        string $side,
        float $qty,
        float $takeProfit,
        float $stopLoss,
        array $opts = []
    ): array {
        Log::info('OCO relink attempt', [
            'symbol' => $symbol,
            'side' => $side,
            'qty' => $qty,
            'take_profit' => $takeProfit,
            'stop_loss' => $stopLoss,
        ]);

        return $this->setupOcoWithRetry($symbol, $side, $qty, $takeProfit, $stopLoss, $opts);
    }
}
