<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Exchange\ExchangeClientInterface;

final class FakeBybitClient implements ExchangeClientInterface
{
    public function __construct(
        private array $tick = [],
        private array $klines = [],
        private array $responses = []
    ) {}

    public function setLeverage(string $symbol, int $leverage, array $opts = []): array
    {
        return $this->responses['setLeverage'] ?? [
            'retCode' => 0,
            'retMsg' => 'OK',
            'result' => ['symbol' => $symbol, 'leverage' => $leverage, 'opts' => $opts],
        ];
    }

    public function createOrder(string $symbol, string $side, string $type, float $qty, ?float $price = null, array $opts = []): array
    {
        return $this->responses['createOrder'] ?? [
            'retCode' => 0,
            'retMsg' => 'OK',
            'result' => ['orderId' => 'mkt-123', 'echo' => compact('symbol', 'side', 'type', 'qty', 'price', 'opts')],
        ];
    }

    public function kline(string $symbol, string $interval = '5', int $limit = 50, ?string $category = null): array
    {
        return ['result' => ['list' => ($this->klines[$symbol] ?? [])]];
    }

    public function tickers(string $symbol, ?string $category = null): array
    {
        return $this->tick ?: ['result' => ['list' => []]];
    }

    public function createOcoOrder(array $params): array
    {
        return $this->responses['createOcoOrder'] ?? [
            'ok' => true,
            'result' => ['orderId' => 'oco-123', 'params' => $params],
            'error_message' => null,
        ];
    }

    public function cancelOcoOrder(array $params): array
    {
        return $this->responses['cancelOcoOrder'] ?? [
            'ok' => true,
            'result' => ['cancelled' => true, 'params' => $params],
            'error_message' => null,
        ];
    }

    public function getOcoOrder(array $params): array
    {
        return $this->responses['getOcoOrder'] ?? [
            'ok' => true,
            'result' => ['orderId' => 'oco-123', 'status' => 'ACTIVE', 'params' => $params],
            'error_message' => null,
        ];
    }

    public function setAccountInfo(array $data): void
    {
        // Mock method for testing
    }

    public function getInstrumentInfo(string $symbol): array
    {
        return $this->responses['getInstrumentInfo'] ?? [
            'retCode' => 0,
            'retMsg' => 'OK',
            'result' => [
                'category' => 'linear',
                'symbol' => $symbol,
                'contractType' => 'LinearPerpetual',
                'status' => 'Trading',
                'baseCoin' => 'BTC',
                'quoteCoin' => 'USDT',
                'launchTime' => '1585526400000',
                'deliveryTime' => '0',
                'deliveryFeeRate' => '',
                'priceScale' => '2',
                'leverageFilter' => [
                    'minLeverage' => '1',
                    'maxLeverage' => '100.00',
                    'leverageStep' => '0.01',
                ],
                'priceFilter' => [
                    'minPrice' => '0.10',
                    'maxPrice' => '199999.80',
                    'tickSize' => '0.10',
                ],
                'lotSizeFilter' => [
                    'maxOrderQty' => '100.000',
                    'maxMktOrderQty' => '100.000',
                    'minOrderQty' => '0.001',
                    'qtyStep' => '0.001',
                    'postOnlyMaxOrderQty' => '1000.000',
                    'minNotionalValue' => '5',
                ],
            ],
        ];
    }

    public function closeReduceOnlyMarket(string $symbol, string $side, string $qty, string $orderLinkId): array
    {
        return $this->responses['closeReduceOnlyMarket'] ?? [
            'retCode' => 0,
            'retMsg' => 'OK',
            'result' => [
                'orderId' => 'close-'.uniqid(),
                'orderLinkId' => $orderLinkId,
                'symbol' => $symbol,
                'side' => $side,
                'orderType' => 'Market',
                'qty' => $qty,
                'reduceOnly' => true,
                'timeInForce' => 'IOC',
                'orderStatus' => 'Filled',
                'avgPrice' => '43250.00',
                'cumExecQty' => $qty,
                'cumExecValue' => (float) $qty * 43250.00,
                'cumExecFee' => '0.00432',
                'createdTime' => now()->timestamp * 1000,
                'updatedTime' => now()->timestamp * 1000,
            ],
        ];
    }
}
