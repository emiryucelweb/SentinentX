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
}
