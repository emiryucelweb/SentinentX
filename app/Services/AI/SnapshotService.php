<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\Indicators\TALib;
use App\Services\Market\BybitMarketData;
use Illuminate\Support\Facades\Cache;

final class SnapshotService
{
    public function __construct(
        private BybitMarketData $marketData,
        private TALib $taLib
    ) {}

    public function createSnapshot(string $symbol, array $options = []): array
    {
        $cacheKey = "snapshot_{$symbol}_".md5(serialize($options));

        return Cache::remember($cacheKey, 60, function () use ($symbol, $options) {
            $timeframe = $options['timeframe'] ?? '1m';
            $limit = $options['limit'] ?? 100;

            // Market verisi
            $klines = $this->marketData->getKlines($symbol, $timeframe, $limit);

            if (empty($klines)) {
                throw new \RuntimeException("Market data not available for {$symbol}");
            }

            // Teknik göstergeler
            $closes = array_column($klines, 'close');
            $highs = array_column($klines, 'high');
            $lows = array_column($klines, 'low');
            $volumes = array_column($klines, 'volume');

            $rsi = $this->taLib->rsi($closes, 14);
            $atr = $this->taLib->atr($highs, $lows, $closes, 14);
            $bbands = $this->taLib->bbands($closes, 20, 2);
            $macd = $this->taLib->macd($closes);

            // Volatilite hesaplama
            $returns = [];
            for ($i = 1; $i < count($closes); $i++) {
                $returns[] = log($closes[$i] / $closes[$i - 1]);
            }
            $volatility = count($returns) > 0 ? sqrt(array_sum(array_map(fn ($r) => $r * $r, $returns)) / count($returns)) : 0;

            // Funding rate (Bybit'ten)
            $fundingRate = $this->marketData->getFundingRate($symbol);

            // Risk parametreleri
            $riskParams = [
                'max_leverage' => config('trading.risk.max_leverage', 20),
                'deviation_threshold' => config('trading.risk.deviation_threshold', 0.20),
                'risk_per_trade' => config('trading.risk.risk_per_trade', 0.02),
                'max_drawdown' => config('trading.risk.max_drawdown', 0.15),
            ];

            // Sistem parametreleri
            $systemParams = [
                'environment' => config('app.env', 'production'),
                'version' => config('app.version', 'v12'),
                'ai_providers_count' => config('ai.providers.count', 3),
                'consensus_method' => config('ai.consensus.method', 'weighted_median'),
                'deviation_veto_enabled' => config('ai.consensus.veto_enabled', true),
            ];

            return [
                'symbol' => $symbol,
                'timestamp' => time(),
                'timeframe' => $timeframe,
                'market' => [
                    'current_price' => end($closes),
                    'open' => $klines[0]['open'],
                    'high' => max($highs),
                    'low' => min($lows),
                    'volume' => array_sum($volumes),
                    'price_change_24h' => $this->calculatePriceChange($closes),
                    'funding_rate' => $fundingRate,
                ],
                'technical' => [
                    'rsi' => end($rsi),
                    'atr' => end($atr),
                    'bbands' => [
                        'upper' => end($bbands['upper']),
                        'middle' => end($bbands['middle']),
                        'lower' => end($bbands['lower']),
                    ],
                    'macd' => [
                        'macd' => end($macd['macd']),
                        'signal' => end($macd['signal']),
                        'histogram' => end($macd['histogram']),
                    ],
                    'volatility' => $volatility,
                ],
                'risk_parameters' => $riskParams,
                'system_parameters' => $systemParams,
                'raw_data' => [
                    'klines' => array_slice($klines, -20), // Son 20 kline
                    'indicators' => [
                        'rsi' => array_slice($rsi, -10),
                        'atr' => array_slice($atr, -10),
                    ],
                ],
            ];
        });
    }

    public function createMultiSymbolSnapshot(array $symbols, array $options = []): array
    {
        $snapshots = [];
        $portfolio = [];

        foreach ($symbols as $symbol) {
            $snapshots[$symbol] = $this->createSnapshot($symbol, $options);
            $portfolio[$symbol] = $snapshots[$symbol]['market']['current_price'];
        }

        // Portfolio korelasyon analizi
        $correlationMatrix = $this->calculatePortfolioCorrelation($symbols);

        return [
            'symbols' => $symbols,
            'timestamp' => time(),
            'snapshots' => $snapshots,
            'portfolio' => [
                'total_value' => array_sum($portfolio),
                'correlation_matrix' => $correlationMatrix,
                'risk_score' => $this->calculatePortfolioRiskScore($correlationMatrix),
            ],
        ];
    }

    private function calculatePriceChange(array $closes): float
    {
        if (count($closes) < 2) {
            return 0;
        }

        $current = end($closes);
        $previous = $closes[count($closes) - 2];

        return (($current - $previous) / $previous) * 100;
    }

    private function calculatePortfolioCorrelation(array $symbols): array
    {
        // Basit korelasyon matrisi (gerçek implementasyonda CorrelationService kullanılabilir)
        $correlation = [];

        foreach ($symbols as $i => $symbol1) {
            foreach ($symbols as $j => $symbol2) {
                if ($i === $j) {
                    $correlation[$symbol1][$symbol2] = 1.0;
                } else {
                    $correlation[$symbol1][$symbol2] = 0.5; // Varsayılan korelasyon
                }
            }
        }

        return $correlation;
    }

    private function calculatePortfolioRiskScore(array $correlationMatrix): float
    {
        // Basit risk skoru (0-1 arası, 1 = yüksek risk)
        $avgCorrelation = 0;
        $count = 0;

        foreach ($correlationMatrix as $symbol1 => $correlations) {
            foreach ($correlations as $symbol2 => $corr) {
                if ($symbol1 !== $symbol2) {
                    $avgCorrelation += $corr;
                    $count++;
                }
            }
        }

        return $count > 0 ? $avgCorrelation / $count : 0;
    }
}
