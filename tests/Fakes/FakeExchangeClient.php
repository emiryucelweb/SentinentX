<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Exchange\ExchangeClientInterface;

class FakeExchangeClient implements ExchangeClientInterface
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
            'ok' => true,
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
            'result' => [
                'ocoId' => 'oco-'.uniqid(),
                'symbol' => $params['symbol'],
                'side' => $params['side'],
                'qty' => $params['qty'],
                'takeProfit' => $params['takeProfit'],
                'stopLoss' => $params['stopLoss'],
            ],
        ];
    }

    public function cancelOrder(array $params): array
    {
        return $this->responses['cancelOrder'] ?? [
            'ok' => true,
            'result' => [
                'orderId' => $params['orderId'],
                'status' => 'Cancelled',
            ],
        ];
    }

    public function cancelOcoOrder(array $params): array
    {
        return $this->responses['cancelOcoOrder'] ?? [
            'ok' => true,
            'result' => [
                'ocoId' => $params['ocoId'] ?? 'oco-'.uniqid(),
                'status' => 'Cancelled',
            ],
        ];
    }

    public function getOcoOrder(array $params): array
    {
        return $this->responses['getOcoOrder'] ?? [
            'ok' => true,
            'result' => [
                'ocoId' => $params['ocoId'] ?? 'oco-'.uniqid(),
                'status' => 'Active',
                'symbol' => $params['symbol'] ?? 'BTCUSDT',
                'side' => $params['side'] ?? 'Buy',
                'qty' => $params['qty'] ?? 1.0,
                'takeProfit' => $params['takeProfit'] ?? 0.0,
                'stopLoss' => $params['stopLoss'] ?? 0.0,
            ],
        ];
    }
}
