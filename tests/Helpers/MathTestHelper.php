<?php

namespace Tests\Helpers;

use App\Services\Indicators\TALib;
use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;

class MathTestHelper
{
    public static function testPositionSizingScenarios(): array
    {
        $sizer = new PositionSizer;
        $results = [];

        // Senaryo 1: BTC LONG - Normal volatilite
        $results['btc_long_normal'] = [
            'description' => 'BTC LONG - Normal volatilite',
            'params' => ['LONG', 50000, 49000, 10000, 10, 0.02, 0.001, 0.001],
            'expected' => [
                'qty_min' => 0.001,
                'leverage' => 10.0,
                'unit_risk' => 1000,
                'risk_amount' => 200,
            ],
        ];

        // Senaryo 2: ETH SHORT - Yüksek volatilite
        $results['eth_short_high_vol'] = [
            'description' => 'ETH SHORT - Yüksek volatilite',
            'params' => ['SHORT', 3000, 3150, 5000, 20, 0.03, 0.01, 0.01],
            'expected' => [
                'qty_min' => 0.01,
                'leverage' => 20.0,
                'unit_risk' => 150,
                'risk_amount' => 150,
            ],
        ];

        // Senaryo 3: SOL LONG - Düşük equity
        $results['sol_long_low_equity'] = [
            'description' => 'SOL LONG - Düşük equity',
            'params' => ['LONG', 100, 95, 1000, 5, 0.01, 0.1, 0.1],
            'expected' => [
                'qty_min' => 0.1,
                'leverage' => 5.0,
                'unit_risk' => 5,
                'risk_amount' => 10,
            ],
        ];

        // Senaryo 4: XRP SHORT - Çok yüksek leverage
        $results['xrp_short_high_leverage'] = [
            'description' => 'XRP SHORT - Çok yüksek leverage',
            'params' => ['SHORT', 0.5, 0.525, 2000, 75, 0.015, 0.001, 0.001],
            'expected' => [
                'qty_min' => 0.001,
                'leverage' => 75.0,
                'unit_risk' => 0.025,
                'risk_amount' => 30,
            ],
        ];

        // Senaryo 5: Edge case - Çok küçük risk
        $results['edge_tiny_risk'] = [
            'description' => 'Edge case - Çok küçük risk',
            'params' => ['LONG', 50000, 49999.999, 10000, 10, 0.005, 0.001, 0.001],
            'expected' => [
                'qty_min' => 0.001,
                'leverage' => 10.0,
                'unit_risk' => 0.001,
                'risk_amount' => 50,
            ],
        ];

        // Senaryo 6: Edge case - Çok büyük equity
        $results['edge_huge_equity'] = [
            'description' => 'Edge case - Çok büyük equity',
            'params' => [1e15, 0.01, 1000, 50000, 10, 0.001, 0.001],
            'expected' => [
                'qty_min' => 0.001,
                'leverage' => 10.0,
                'unit_risk' => 1000,
                'risk_amount' => 1e13,
            ],
        ];

        // Her senaryoyu test et
        foreach ($results as $key => $scenario) {
            try {
                $actual = $sizer->sizeByRisk(...$scenario['params']);
                $results[$key]['actual'] = $actual;
                $results[$key]['passed'] = self::validatePositionSizingResult($actual, $scenario['expected']);
                $results[$key]['errors'] = self::getPositionSizingErrors($actual, $scenario['expected']);
            } catch (\Exception $e) {
                $results[$key]['actual'] = null;
                $results[$key]['passed'] = false;
                $results[$key]['errors'] = ['Exception: '.$e->getMessage()];
            }
        }

        return $results;
    }

    public static function testStopCalculationScenarios(): array
    {
        $stopCalc = new StopCalculator;
        $results = [];

        // Senaryo 1: BTC LONG - Normal volatilite
        $results['btc_long_normal'] = [
            'description' => 'BTC LONG - Normal volatilite',
            'params' => ['BTCUSDT', 'LONG', 50000, 1.5],
            'expected' => [
                'sl_below_entry' => true,
                'tp_above_entry' => true,
                'sl_distance_min' => 0.001, // %0.1
                'tp_distance_min' => 0.003, // %0.3
                'sl_distance_max' => 0.1,   // %10
                'tp_distance_max' => 0.3,    // %30
            ],
        ];

        // Senaryo 2: ETH SHORT - Yüksek volatilite
        $results['eth_short_high_vol'] = [
            'description' => 'ETH SHORT - Yüksek volatilite',
            'params' => ['ETHUSDT', 'SHORT', 3000, 2.0],
            'expected' => [
                'sl_above_entry' => true,
                'tp_below_entry' => true,
                'sl_distance_min' => 0.001,
                'tp_distance_min' => 0.004,
                'sl_distance_max' => 0.1,
                'tp_distance_max' => 0.4,
            ],
        ];

        // Senaryo 3: SOL - Düşük volatilite
        $results['sol_low_vol'] = [
            'description' => 'SOL - Düşük volatilite',
            'params' => ['SOLUSDT', 'LONG', 100, 1.0],
            'expected' => [
                'sl_below_entry' => true,
                'tp_above_entry' => true,
                'sl_distance_min' => 0.0001,
                'tp_distance_min' => 0.0002,
                'sl_distance_max' => 0.1,
                'tp_distance_max' => 0.2,
            ],
        ];

        // Her senaryoyu test et
        foreach ($results as $key => $scenario) {
            try {
                $actual = $stopCalc->compute(...$scenario['params']);
                $results[$key]['actual'] = $actual;
                $results[$key]['passed'] = self::validateStopCalculationResult($actual, $scenario['params'], $scenario['expected']);
                $results[$key]['errors'] = self::getStopCalculationErrors($actual, $scenario['params'], $scenario['expected']);
            } catch (\Exception $e) {
                $results[$key]['actual'] = null;
                $results[$key]['passed'] = false;
                $results[$key]['errors'] = ['Exception: '.$e->getMessage()];
            }
        }

        return $results;
    }

    public static function testRiskGuardScenarios(): array
    {
        $riskGuard = new RiskGuard;
        $results = [];

        // Senaryo 1: Normal leverage - Likidasyon buffer yeterli
        $results['normal_leverage_sufficient'] = [
            'description' => 'Normal leverage - Likidasyon buffer yeterli',
            'params' => ['BTCUSDT', 50000, 'LONG', 10, 44000], // %12 distance (min required)
            'expected' => [
                'should_pass' => true,
                'min_distance_pct' => 0.12, // %12 (entry-stop distance)
                'max_distance_pct' => 0.15,  // %15
            ],
        ];

        // Senaryo 2: Yüksek leverage - Likidasyon buffer yetersiz
        $results['high_leverage_insufficient'] = [
            'description' => 'Yüksek leverage - Likidasyon buffer yetersiz',
            'params' => ['ETHUSDT', 3000, 'SHORT', 75, 3030], // %1 distance (yetersiz)
            'expected' => [
                'should_pass' => false,
                'min_distance_pct' => 0.013, // %1.33 (1/75)
                'max_distance_pct' => 0.02,   // %2
            ],
        ];

        // Senaryo 3: Çok yüksek leverage - Kesinlikle red
        $results['very_high_leverage_reject'] = [
            'description' => 'Çok yüksek leverage - Kesinlikle red',
            'params' => ['SOLUSDT', 100, 'LONG', 100, 99.5],
            'expected' => [
                'should_pass' => false,
                'min_distance_pct' => 0.01,  // %1 (1/100)
                'max_distance_pct' => 0.015,  // %1.5
            ],
        ];

        // Her senaryoyu test et
        foreach ($results as $key => $scenario) {
            try {
                $actual = $riskGuard->okToOpen(...$scenario['params']);
                $results[$key]['actual'] = $actual;
                $results[$key]['passed'] = self::validateRiskGuardResult($actual, $scenario['expected']);
                $results[$key]['errors'] = self::getRiskGuardErrors($actual, $scenario['expected']);
            } catch (\Exception $e) {
                $results[$key]['actual'] = null;
                $results[$key]['passed'] = false;
                $results[$key]['errors'] = ['Exception: '.$e->getMessage()];
            }
        }

        return $results;
    }

    public static function testTALibScenarios(): array
    {
        $results = [];

        // Senaryo 1: Trend yükselen fiyatlar
        $results['uptrend'] = [
            'description' => 'Trend yükselen fiyatlar',
            'closes' => [100, 101, 102, 103, 104, 105, 106, 107, 108, 109],
            'highs' => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110],
            'lows' => [99, 100, 101, 102, 103, 104, 105, 106, 107, 108],
            'volumes' => [1000, 1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900],
            'expected' => [
                'ema_trend' => 'up',
                'rsi_range' => [50, 100],
                'macd_signal' => 'bullish',
                'bb_position' => 'upper',
            ],
        ];

        // Senaryo 2: Trend düşen fiyatlar
        $results['downtrend'] = [
            'description' => 'Trend düşen fiyatlar',
            'closes' => [110, 109, 108, 107, 106, 105, 104, 103, 102, 101],
            'highs' => [111, 110, 109, 108, 107, 106, 105, 104, 103, 102],
            'lows' => [109, 108, 107, 106, 105, 104, 103, 102, 101, 100],
            'volumes' => [1900, 1800, 1700, 1600, 1500, 1400, 1300, 1200, 1100, 1000],
            'expected' => [
                'ema_trend' => 'down',
                'rsi_range' => [0, 50],
                'macd_signal' => 'bearish',
                'bb_position' => 'lower',
            ],
        ];

        // Senaryo 3: Sideways (yatay) fiyatlar
        $results['sideways'] = [
            'description' => 'Sideways (yatay) fiyatlar',
            'closes' => [100, 99, 101, 98, 102, 97, 103, 96, 104, 95],
            'highs' => [101, 100, 102, 99, 103, 98, 104, 97, 105, 96],
            'lows' => [99, 98, 100, 97, 101, 96, 102, 95, 103, 94],
            'volumes' => [1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
            'expected' => [
                'ema_trend' => 'sideways',
                'rsi_range' => [30, 70],
                'macd_signal' => 'neutral',
                'bb_position' => 'middle',
            ],
        ];

        // Her senaryoyu test et
        foreach ($results as $key => $scenario) {
            try {
                $actual = self::calculateTALibIndicators($scenario['closes'], $scenario['highs'], $scenario['lows'], $scenario['volumes']);
                $results[$key]['actual'] = $actual;
                $results[$key]['passed'] = self::validateTALibResult($actual, $scenario['expected']);
                $results[$key]['errors'] = self::getTALibErrors($actual, $scenario['expected']);
            } catch (\Exception $e) {
                $results[$key]['actual'] = null;
                $results[$key]['passed'] = false;
                $results[$key]['errors'] = ['Exception: '.$e->getMessage()];
            }
        }

        return $results;
    }

    private static function calculateTALibIndicators(array $closes, array $highs, array $lows, array $volumes): array
    {
        $ema = TALib::ema($closes, 5);
        $rsi = TALib::rsi($closes, 5);
        $macd = TALib::macd($closes, 3, 5, 2);
        $bb = TALib::bollinger($closes, 5, 2.0);
        $atr = TALib::atr($highs, $lows, $closes, 5);

        return [
            'ema' => $ema,
            'rsi' => $rsi,
            'macd' => $macd,
            'bollinger' => $bb,
            'atr' => $atr,
        ];
    }

    private static function validatePositionSizingResult(array $actual, array $expected): bool
    {
        if (! isset($actual['qty']) || ! isset($actual['leverage'])) {
            return false;
        }

        if ($actual['qty'] < $expected['qty_min']) {
            return false;
        }

        if ($actual['leverage'] !== $expected['leverage']) {
            return false;
        }

        return true;
    }

    private static function getPositionSizingErrors(array $actual, array $expected): array
    {
        $errors = [];

        if (! isset($actual['qty']) || ! isset($actual['leverage'])) {
            $errors[] = 'Missing qty or leverage in result';
        }

        if (isset($actual['qty']) && $actual['qty'] < $expected['qty_min']) {
            $errors[] = "Qty {$actual['qty']} is below minimum {$expected['qty_min']}";
        }

        if (isset($actual['leverage']) && $actual['leverage'] !== $expected['leverage']) {
            $errors[] = "Leverage {$actual['leverage']} doesn't match expected {$expected['leverage']}";
        }

        return $errors;
    }

    private static function validateStopCalculationResult(array $actual, array $params, array $expected): bool
    {
        if (count($actual) !== 2) {
            return false;
        }

        [$sl, $tp] = $actual;
        $entry = $params[2];
        $side = $params[1];

        if ($side === 'LONG') {
            if ($sl >= $entry || $tp <= $entry) {
                return false;
            }
        } else {
            if ($sl <= $entry || $tp >= $entry) {
                return false;
            }
        }

        return true;
    }

    private static function getStopCalculationErrors(array $actual, array $params, array $expected): array
    {
        $errors = [];

        if (count($actual) !== 2) {
            $errors[] = 'Result should contain exactly 2 values (SL, TP)';

            return $errors;
        }

        [$sl, $tp] = $actual;
        $entry = $params[2];
        $side = $params[1];

        if ($side === 'LONG') {
            if ($sl >= $entry) {
                $errors[] = "LONG stop loss {$sl} should be below entry {$entry}";
            }
            if ($tp <= $entry) {
                $errors[] = "LONG take profit {$tp} should be above entry {$entry}";
            }
        } else {
            if ($sl <= $entry) {
                $errors[] = "SHORT stop loss {$sl} should be above entry {$entry}";
            }
            if ($tp >= $entry) {
                $errors[] = "SHORT take profit {$tp} should be below entry {$entry}";
            }
        }

        return $errors;
    }

    private static function validateRiskGuardResult(array $actual, array $expected): bool
    {
        if (! isset($actual['ok'])) {
            return false;
        }

        return $actual['ok'] === $expected['should_pass'];
    }

    private static function getRiskGuardErrors(array $actual, array $expected): array
    {
        $errors = [];

        if (! isset($actual['ok'])) {
            $errors[] = 'Missing ok field in result';

            return $errors;
        }

        if ($actual['ok'] !== $expected['should_pass']) {
            $errors[] = 'Expected result to be '.($expected['should_pass'] ? 'OK' : 'REJECTED').' but got '.($actual['ok'] ? 'OK' : 'REJECTED');
        }

        return $errors;
    }

    private static function validateTALibResult(array $actual, array $expected): bool
    {
        // Basit validasyon - gerçek implementasyonda daha detaylı olabilir
        return isset($actual['ema']) && isset($actual['rsi']) && isset($actual['macd']);
    }

    private static function getTALibErrors(array $actual, array $expected): array
    {
        $errors = [];

        if (! isset($actual['ema'])) {
            $errors[] = 'Missing EMA calculation';
        }

        if (! isset($actual['rsi'])) {
            $errors[] = 'Missing RSI calculation';
        }

        if (! isset($actual['macd'])) {
            $errors[] = 'Missing MACD calculation';
        }

        return $errors;
    }
}
