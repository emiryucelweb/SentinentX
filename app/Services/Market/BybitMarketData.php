<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Services\Exchange\BybitClient;
use Illuminate\Support\Arr;

class BybitMarketData
{
    public function __construct(private readonly BybitClient $bybit) {}

    public function snapshot(string $symbol, string $interval = '5', int $limit = 50, ?string $category = null): array
    {
        $klineResponse = $this->bybit->kline($symbol, $interval, $limit, $category);
        $klineList = (array) Arr::get($klineResponse, 'result.list', []);

        $close = 0.0;
        $atr = 0.0;

        if (! empty($klineList)) {
            $lastCandle = $klineList[0]; // Bybit API v5 en güncel mumu ilk sırada [0] döndürür
            $close = isset($lastCandle[4]) ? (float) $lastCandle[4] : 0.0;

            if (count($klineList) > 1) {
                $trueRanges = [];
                for ($i = 0; $i < count($klineList) - 1; $i++) {
                    $high = (float) $klineList[$i][2];
                    $low = (float) $klineList[$i][3];
                    $prevClose = (float) $klineList[$i + 1][4];
                    $trueRanges[] = max($high - $low, abs($high - $prevClose), abs($low - $prevClose));
                }
                if (! empty($trueRanges)) {
                    $atr = array_sum($trueRanges) / count($trueRanges);
                }
            }
        }

        if ($close <= 0) {
            $close = $this->lastPrice($symbol, $category);
        }

        return [
            'symbol' => $symbol,
            'price' => $close,
            'atr' => $atr,
            'kline' => $klineList,
        ];
    }

    public function lastPrice(string $symbol, ?string $category = null): float
    {
        $category = $category ?? config('exchange.bybit.category', 'linear');
        $response = $this->bybit->tickers($symbol, $category);
        $price = Arr::get($response, 'result.list.0.lastPrice', '0.0');

        return (float) $price;
    }
}
