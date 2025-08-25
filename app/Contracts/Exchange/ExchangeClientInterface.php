<?php

declare(strict_types=1);

namespace App\Contracts\Exchange;

interface ExchangeClientInterface
{
    /**
     * Standart hata/ret semantiği:
     * Başarı: ['ok'=>true,'result'=>...]
     * Hata: ['ok'=>false,'code'=>'POST_ONLY_REJECT','message'=>'...']
     */
    public function setLeverage(string $symbol, int $leverage, array $opts = []): array;

    public function createOrder(
        string $symbol,
        string $side,
        string $type,
        float $qty,
        ?float $price = null,
        array $opts = []
    ): array;

    public function kline(string $symbol, string $interval = '5', int $limit = 50, ?string $category = null): array;

    public function tickers(string $symbol, ?string $category = null): array;

    /**
     * OCO (One Cancels Other) order oluştur
     *
     * @param  array  $params  OCO parametreleri
     * @return array ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function createOcoOrder(array $params): array;

    /**
     * OCO order iptal et
     *
     * @param  array  $params  İptal parametreleri
     * @return array ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function cancelOcoOrder(array $params): array;

    /**
     * OCO order bilgisi al
     *
     * @param  array  $params  Sorgu parametreleri
     * @return array ['ok' => bool, 'result' => array, 'error_message' => string|null]
     */
    public function getOcoOrder(array $params): array;

    /**
     * Get instrument information (tick size, price filters, etc.)
     *
     * @return array<string, mixed>|null Instrument info or null if not found
     */
    public function getInstrumentInfo(string $symbol): ?array;

    /**
     * Close position with reduce-only market order
     *
     * @return array<string, mixed>
     */
    public function closeReduceOnlyMarket(string $symbol, string $side, string $qty, string $orderLinkId): array;
}
