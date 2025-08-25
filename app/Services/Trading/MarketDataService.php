<?php

declare(strict_types=1);

namespace App\Services\Trading;

/**
 * Market Data Service - provides market information for trading decisions
 */
class MarketDataService
{
    public function getBestPrice(string $symbol): float
    {
        // Mock implementation for testing
        return match ($symbol) {
            'BTCUSDT' => 50000.0,
            'ETHUSDT' => 3000.0,
            'SOLUSDT' => 100.0,
            'XRPUSDT' => 0.6,
            default => 1.0
        };
    }

    public function getCurrentPrice(string $symbol): float
    {
        // Slightly different from best price to simulate spread
        return $this->getBestPrice($symbol) * 1.0005; // 0.05% spread
    }

    public function getVolatility(string $symbol): float
    {
        // Mock volatility data
        return match ($symbol) {
            'BTCUSDT' => 0.02, // 2%
            'ETHUSDT' => 0.025, // 2.5%
            'SOLUSDT' => 0.04, // 4%
            'XRPUSDT' => 0.03, // 3%
            default => 0.01
        };
    }

    public function getLiquidityScore(string $symbol): float
    {
        // Mock liquidity score (0-1, higher is better)
        return match ($symbol) {
            'BTCUSDT' => 0.95,
            'ETHUSDT' => 0.90,
            'SOLUSDT' => 0.75,
            'XRPUSDT' => 0.70,
            default => 0.50
        };
    }

    public function getSpread(string $symbol): float
    {
        return abs($this->getCurrentPrice($symbol) - $this->getBestPrice($symbol));
    }

    public function getOrderBookDepth(string $symbol, int $levels = 5): array
    {
        $bestPrice = $this->getBestPrice($symbol);
        $spread = $this->getSpread($symbol);

        $bids = [];
        $asks = [];

        for ($i = 0; $i < $levels; $i++) {
            $bids[] = [
                'price' => $bestPrice - ($spread * $i),
                'quantity' => rand(100, 1000) / 100, // Random quantity
            ];

            $asks[] = [
                'price' => $bestPrice + ($spread * ($i + 1)),
                'quantity' => rand(100, 1000) / 100,
            ];
        }

        return [
            'bids' => $bids,
            'asks' => $asks,
            'timestamp' => time(),
        ];
    }
}
