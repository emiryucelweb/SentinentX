<?php

declare(strict_types=1);

namespace App\Services\Trading;

class StopCalculator
{
    /**
     * Standart compute metodu: symbol, action, price, atrK → [sl, tp]
     *
     * @param  string  $symbol  Sembol (gelecekte ATR hesaplama için kullanılabilir)
     * @param  string  $action  LONG|SHORT
     * @param  float  $price  Giriş fiyatı
     * @param  float  $atrK  ATR çarpanı
     * @return array [stopLoss, takeProfit]
     */
    public function compute(string $symbol, string $action, float $price, float $atrK): array
    {
        // Input validation
        if ($price <= 0.0) {
            \Log::error('StopCalculator: Invalid price', ['price' => $price, 'symbol' => $symbol]);

            return [0.0, 0.0];
        }

        if ($atrK <= 0.0) {
            \Log::error('StopCalculator: Invalid ATR multiplier', ['atrK' => $atrK, 'symbol' => $symbol]);

            return [0.0, 0.0];
        }

        // Basit ATR hesaplama (gerçek implementasyon için ATR service eklenebilir)
        $atr = $this->calculateSafeATR($price, $symbol);

        $sl = $this->atrStop($action, $price, $atr, $atrK);
        $tp = $this->atrTakeProfit($action, $price, $atr, $atrK);

        return [$sl, $tp];
    }

    /**
     * Stop-Limit SL hesaplama (opsiyonel)
     * Şartname: Stop-Limit SL opsiyonu eklenebilir
     */
    public function computeStopLimit(string $symbol, string $action, float $price, float $atrK): array
    {
        [$sl, $tp] = $this->compute($symbol, $action, $price, $atrK);

        // Stop-Limit için ek offset (ATR'nin %10'u)
        $offset = $this->calculateSafeATR($price, $symbol) * 0.1;

        $stopLimit = match (strtoupper($action)) {
            'LONG' => $sl + $offset,  // SL'den biraz yukarı
            'SHORT' => $sl - $offset,  // SL'den biraz aşağı
            default => $sl,
        };

        return [
            'stop_loss' => $sl,
            'take_profit' => $tp,
            'stop_limit' => $stopLimit,
            'offset' => $offset,
        ];
    }

    public function atrStop(string $side, float $entry, float $atr, ?float $k = null): float
    {
        // Input validation
        if ($entry <= 0.0 || $atr <= 0.0) {
            \Log::error('StopCalculator: Invalid input for ATR stop', [
                'entry' => $entry,
                'atr' => $atr,
                'side' => $side,
            ]);

            return 0.0;
        }

        $kk = $k ?? (float) config('trading.risk.atr_k', 1.5);

        // ATR multiplier validation
        if ($kk <= 0.0) {
            \Log::error('StopCalculator: Invalid ATR multiplier', ['k' => $kk]);

            return 0.0;
        }

        $delta = $this->safeMultiplication($kk, $atr);

        return match (strtoupper($side)) {
            'LONG' => max(0.0, $entry - $delta),
            'SHORT' => max(0.0, $entry + $delta),
            default => $entry,
        };
    }

    public function atrTakeProfit(string $side, float $entry, float $atr, ?float $kTp = null): float
    {
        // Input validation
        if ($entry <= 0.0 || $atr <= 0.0) {
            \Log::error('StopCalculator: Invalid input for ATR take profit', [
                'entry' => $entry,
                'atr' => $atr,
                'side' => $side,
            ]);

            return 0.0;
        }

        $kk = $kTp ?? (float) config('trading.risk.tp_k', 3.0);

        // ATR multiplier validation
        if ($kk <= 0.0) {
            \Log::error('StopCalculator: Invalid ATR multiplier', ['k' => $kk]);

            return 0.0;
        }

        $delta = $this->safeMultiplication($kk, $atr);

        return match (strtoupper($side)) {
            'LONG' => max(0.0, $entry + $delta),
            'SHORT' => max(0.0, $entry - $delta),
            default => $entry,
        };
    }

    /**
     * Güvenli ATR hesaplama
     */
    private function calculateSafeATR(float $price, string $symbol): float
    {
        // Basit ATR hesaplama (gerçek implementasyon için ATR service eklenebilir)
        $baseVolatility = config('trading.risk.base_volatility_pct', 0.003); // %0.3 default

        // Symbol-specific volatility override
        $symbolVolatility = config("trading.risk.symbols.{$symbol}.volatility_pct", $baseVolatility);

        $atr = $price * $symbolVolatility;

        // Minimum ATR threshold
        $minATR = config('trading.risk.min_atr_threshold', 0.0001);
        $atr = max($minATR, $atr);

        // Maximum ATR threshold (extreme volatility protection)
        $maxATR = config('trading.risk.max_atr_threshold', 0.1); // %10
        $atr = min($atr, $price * $maxATR);

        return $atr;
    }

    /**
     * Güvenli multiplication (overflow protection)
     */
    private function safeMultiplication(float $a, float $b): float
    {
        if (config('trading.risk.position_sizing.overflow_protection', true)) {
            // Overflow check
            if ($a > 0 && $b > 0 && $a > PHP_FLOAT_MAX / $b) {
                \Log::warning('StopCalculator: Multiplication overflow prevented', [
                    'a' => $a,
                    'b' => $b,
                    'max' => PHP_FLOAT_MAX,
                ]);

                return PHP_FLOAT_MAX;
            }

            if ($a < 0 && $b < 0 && $a < PHP_FLOAT_MIN / $b) {
                \Log::warning('StopCalculator: Multiplication underflow prevented', [
                    'a' => $a,
                    'b' => $b,
                    'min' => PHP_FLOAT_MIN,
                ]);

                return PHP_FLOAT_MIN;
            }
        }

        return $a * $b;
    }

    /**
     * Güvenlik parametrelerini kontrol et
     */
    public function validateSafetyParameters(): array
    {
        $config = config('trading.risk', []);

        $validation = [
            'atr_k' => [
                'value' => $config['atr_k'] ?? 1.5,
                'valid' => ($config['atr_k'] ?? 1.5) > 0.0,
                'message' => 'ATR K must be positive',
            ],
            'tp_k' => [
                'value' => $config['tp_k'] ?? 3.0,
                'valid' => ($config['tp_k'] ?? 3.0) > 0.0,
                'message' => 'TP K must be positive',
            ],
            'base_volatility_pct' => [
                'value' => $config['base_volatility_pct'] ?? 0.003,
                'valid' => ($config['base_volatility_pct'] ?? 0.003) > 0.0
                    && ($config['base_volatility_pct'] ?? 0.003) < 1.0,
                'message' => 'Base volatility must be between 0 and 1',
            ],
            'min_atr_threshold' => [
                'value' => $config['min_atr_threshold'] ?? 0.0001,
                'valid' => ($config['min_atr_threshold'] ?? 0.0001) > 0.0,
                'message' => 'Min ATR threshold must be positive',
            ],
            'max_atr_threshold' => [
                'value' => $config['max_atr_threshold'] ?? 0.1,
                'valid' => ($config['max_atr_threshold'] ?? 0.1) > 0.0 && ($config['max_atr_threshold'] ?? 0.1) < 1.0,
                'message' => 'Max ATR threshold must be between 0 and 1',
            ],
        ];

        $allValid = array_reduce($validation, fn ($carry, $item) => $carry && $item['valid'], true);

        return [
            'valid' => $allValid,
            'details' => $validation,
        ];
    }
}
