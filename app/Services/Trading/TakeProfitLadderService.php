<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Support\Facades\Log;

final class TakeProfitLadderService
{
    public function __construct(
        private ExchangeClientInterface $exchange,
        private int $maxRetries = 3,
        private int $retryDelaySeconds = 2
    ) {}

    /**
     * TP Merdiveni kur - Multiple take profit seviyeleri
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  float  $qty  Toplam miktar
     * @param  float  $entryPrice  Giriş fiyatı
     * @param  array  $tpLevels  TP seviyeleri [['price' => float, 'percentage' => float], ...]
     * @param  array  $opts  Ek options
     * @return array ['ok' => bool, 'tp_orders' => array, 'details' => array]
     */
    /**
     * @param array<string, mixed> $tpLevels
     * @param array<string, mixed> $opts
     * @return array<string, mixed>
     */
    public function setupTpLadder(
        string $symbol,
        string $side,
        float $qty,
        float $entryPrice,
        array $tpLevels,
        array $opts = []
    ): array {
        $side = strtoupper($side);
        $exchangeSide = $side === 'LONG' ? 'Sell' : 'Buy'; // TP için ters side

        // TP seviyelerini sırala (LONG için yükselen, SHORT için düşen)
        $sortedLevels = $this->sortTpLevels($tpLevels, $side);

        // Her seviye için miktar hesapla
        $levelQtys = $this->calculateLevelQuantities($qty, $sortedLevels);

        $tpOrders = [];

        foreach ($sortedLevels as $index => $level) {
            $levelQty = $levelQtys[$index];

            // TP order'ı oluştur (retry ile)
            $tpOrder = $this->createTpOrderWithRetry(
                $symbol,
                $exchangeSide,
                $levelQty,
                $level['price'],
                $level['percentage'],
                $opts
            );

            if ($tpOrder['ok']) {
                $tpOrders[] = $tpOrder;
            } else {
                Log::warning('TP Ladder level failed', [
                    'symbol' => $symbol,
                    'level' => $index + 1,
                    'price' => $level['price'],
                    'percentage' => $level['percentage'],
                    'error' => $tpOrder['error'] ?? 'Unknown error',
                ]);
            }
        }

        $successCount = count(array_filter($tpOrders, fn ($order) => $order['ok']));

        return [
            'ok' => $successCount > 0,
            'tp_orders' => $tpOrders,
            'total_levels' => count($sortedLevels),
            'successful_levels' => $successCount,
            'total_qty' => $qty, // Input'taki orijinal qty kullan
            'entry_price' => $entryPrice,
            'side' => $side,
            'details' => [
                'symbol' => $symbol,
                'tp_levels' => $sortedLevels,
                'level_quantities' => $levelQtys,
            ],
        ];
    }

    /**
     * TP seviyelerini sırala
     *
     * @param  array<string, mixed>  $tpLevels  TP seviyeleri
     * @param  string  $side  LONG/SHORT
     * @return array<string, mixed> Sıralanmış seviyeler
     */
    private function sortTpLevels(array $tpLevels, string $side): array
    {
        if ($side === 'LONG') {
            // LONG için fiyatları yükselen sırada sırala
            usort($tpLevels, fn ($a, $b) => $a['price'] <=> $b['price']);
        } else {
            // SHORT için fiyatları düşen sırada sırala
            usort($tpLevels, fn ($a, $b) => $b['price'] <=> $a['price']);
        }

        return $tpLevels;
    }

    /**
     * Her seviye için miktar hesapla
     *
     * @param  float  $totalQty  Toplam miktar
     * @param  array<string, mixed>  $tpLevels  TP seviyeleri
     * @return array<string, mixed> Her seviye için miktar
     */
    private function calculateLevelQuantities(float $totalQty, array $tpLevels): array
    {
        $levelCount = count($tpLevels);
        $baseQty = $totalQty / $levelCount;

        $levelQtys = [];
        $remainingQty = $totalQty;

        for ($i = 0; $i < $levelCount; $i++) {
            if ($i === $levelCount - 1) {
                // Son seviyede kalan tüm miktarı kullan
                $levelQtys[] = round($remainingQty, 8);
            } else {
                $levelQtys[] = round($baseQty, 8);
                $remainingQty -= $baseQty;
            }
        }

        // Floating point precision sorununu çöz
        $totalCalculated = array_sum($levelQtys);
        if (abs($totalCalculated - $totalQty) < 0.00000001) {
            $levelQtys[count($levelQtys) - 1] = round($totalQty - array_sum(array_slice($levelQtys, 0, -1)), 8);
        }

        return $levelQtys;
    }

    /**
     * Tek TP order'ı oluştur
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  Buy/Sell
     * @param  float  $qty  Miktar
     * @param  float  $price  TP fiyatı
     * @param  float  $percentage  TP yüzdesi
     * @param  array<string, mixed>  $opts  Ek options
     * @return array<string, mixed> Order sonucu
     */
    private function createTpOrder(
        string $symbol,
        string $side,
        float $qty,
        float $price,
        float $percentage,
        array $opts
    ): array {
        $orderParams = [
            'category' => $opts['category'] ?? 'linear',
            'symbol' => $symbol,
            'side' => $side,
            'qty' => $qty,
            'price' => $price,
            'orderType' => 'Limit',
            'timeInForce' => 'GTC',
            'reduceOnly' => true,
            'orderLinkId' => $opts['orderLinkId'] ?? null,
        ];

        try {
            $response = $this->exchange->createOrder(
                $symbol,
                $side,
                'LIMIT',
                $qty,
                $price,
                $orderParams
            );

            if ($response['ok'] ?? false) {
                return [
                    'ok' => true,
                    'order_id' => $response['result']['orderId'] ?? null,
                    'price' => $price,
                    'percentage' => $percentage,
                    'qty' => $qty,
                    'side' => $side,
                    'details' => $response['result'] ?? [],
                ];
            }

            return [
                'ok' => false,
                'order_id' => null,
                'error' => $response['error_message'] ?? 'Unknown error',
                'price' => $price,
                'percentage' => $percentage,
                'qty' => $qty,
                'details' => $response,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'order_id' => null,
                'error' => $e->getMessage(),
                'price' => $price,
                'percentage' => $percentage,
                'qty' => $qty,
                'details' => [],
            ];
        }
    }

    /**
     * Tek TP order'ı retry ile oluştur (exponential backoff + jitter)
     */
    private function createTpOrderWithRetry(
        string $symbol,
        string $side,
        float $qty,
        float $price,
        float $percentage,
        array $opts
    ): array {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            $result = $this->createTpOrder($symbol, $side, $qty, $price, $percentage, $opts);
            if ($result['ok'] ?? false) {
                $result['attempt'] = $attempt;

                return $result;
            }

            $lastError = $result['error'] ?? 'Unknown error';
            Log::warning('createTpOrder retry', [
                'symbol' => $symbol,
                'price' => $price,
                'percentage' => $percentage,
                'attempt' => $attempt,
                'max' => $this->maxRetries,
                'error' => $lastError,
            ]);

            if ($attempt < $this->maxRetries) {
                $this->smartDelaySeconds($attempt);
            }
        }

        return [
            'ok' => false,
            'order_id' => null,
            'error' => $lastError,
            'price' => $price,
            'percentage' => $percentage,
            'qty' => $qty,
            'details' => [],
            'attempt' => $attempt,
        ];
    }

    /**
     * Exponential backoff + jitter (saniye cinsinden)
     */
    private function smartDelaySeconds(int $attempt): void
    {
        $base = max(0.01, $this->retryDelaySeconds * (2 ** ($attempt - 1))); // 2^n backoff
        $jitter = $base * 0.2 * (mt_rand(-100, 100) / 100); // ±20%
        $final = $base + $jitter;

        // Test ortamında minimuma indir
        if (app()->environment('testing')) {
            $final = max(0.001, $final * 0.01);
        } else {
            $final = max(0.05, $final);
        }

        usleep((int) ($final * 1_000_000));
    }

    /**
     * TP Merdiveni durumunu kontrol et
     *
     * @param  array  $tpOrders  TP order'ları
     * @return array Durum bilgisi
     */
    public function checkTpLadderStatus(array $tpOrders): array
    {
        $totalOrders = count($tpOrders);
        $filledOrders = 0;
        $pendingOrders = 0;
        $cancelledOrders = 0;

        foreach ($tpOrders as $order) {
            if (! $order['ok']) {
                continue;
            }

            // Order durumunu kontrol et (gerçek implementasyonda exchange'den çekilmeli)
            $status = $this->getOrderStatus($order['order_id']);

            switch ($status) {
                case 'Filled':
                    $filledOrders++;
                    break;
                case 'Pending':
                    $pendingOrders++;
                    break;
                case 'Cancelled':
                    $cancelledOrders++;
                    break;
            }
        }

        return [
            'total_orders' => $totalOrders,
            'filled_orders' => $filledOrders,
            'pending_orders' => $pendingOrders,
            'cancelled_orders' => $cancelledOrders,
            'completion_rate' => $totalOrders > 0 ? ($filledOrders / $totalOrders) * 100 : 0,
        ];
    }

    /**
     * Order durumunu al (placeholder - gerçek implementasyonda exchange'den çekilmeli)
     *
     * @param  string|null  $orderId  Order ID
     * @return string Order durumu
     */
    private function getOrderStatus(?string $orderId): string
    {
        if (! $orderId) {
            return 'Unknown';
        }

        // Gerçek implementasyonda exchange'den order status çekilmeli
        // Şimdilik test için basit bir mock
        return 'Pending';
    }

    /**
     * TP Merdiveni'ni kapat (tüm pending order'ları iptal et)
     *
     * @param  array  $tpOrders  TP order'ları
     * @return array Kapatma sonucu
     */
    public function closeTpLadder(array $tpOrders): array
    {
        $cancelledOrders = 0;
        $errors = [];

        foreach ($tpOrders as $order) {
            if (! $order['ok'] || ! $order['order_id']) {
                continue;
            }

            try {
                $response = $this->exchange->cancelOrder([
                    'category' => 'linear',
                    'orderId' => $order['order_id'],
                ]);

                if ($response['ok'] ?? false) {
                    $cancelledOrders++;
                } else {
                    $errors[] = "Failed to cancel order {$order['order_id']}: ".
                               ($response['error_message'] ?? 'Unknown error');
                }
            } catch (\Throwable $e) {
                $errors[] = "Exception cancelling order {$order['order_id']}: ".$e->getMessage();
            }
        }

        return [
            'ok' => count($errors) === 0,
            'cancelled_orders' => $cancelledOrders,
            'total_orders' => count($tpOrders),
            'errors' => $errors,
        ];
    }
}
