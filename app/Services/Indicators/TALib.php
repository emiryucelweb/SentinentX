<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class TALib
{
    /**
     * @param  array<int, float>  $closes
     * @return array<int, float>
     */
    public static function ema(array $closes, int $period): array
    {
        $k = 2 / ($period + 1);
        $ema = [];
        $prev = null;
        foreach ($closes as $i => $c) {
            $prev = $prev === null ? $c : ($c - $prev) * $k + $prev;
            $ema[$i] = $prev;
        }

        return $ema;
    }

    /**
     * @param  array<int, float>  $closes
     * @return array<int, float>
     */
    public static function rsi(array $closes, int $period = 14): array
    {
        $gains = $losses = [];
        $rsi = [];
        for ($i = 1; $i < count($closes); $i++) {
            $chg = $closes[$i] - $closes[$i - 1];
            $gains[$i] = max(0, $chg);
            $losses[$i] = max(0, -$chg);
        }
        $avgG = $avgL = null;
        for ($i = 1; $i < count($closes); $i++) {
            if ($i <= $period) {
                $avgG = ($avgG ?? 0) + $gains[$i] / $period;
                $avgL = ($avgL ?? 0) + $losses[$i] / $period;
                $rsi[$i] = null;
            } else {
                $avgG = ($avgG * ($period - 1) + $gains[$i]) / $period;
                $avgL = ($avgL * ($period - 1) + $losses[$i]) / $period;
                $rs = $avgL == 0 ? 100 : $avgG / $avgL;
                $rsi[$i] = 100 - (100 / (1 + $rs));
            }
        }

        return array_map(fn ($v) => (float) ($v ?? 0), $rsi);
    }

    /**
     * @param  array<int, float>  $closes
     * @return array<string, array<int, float>>
     */
    public static function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $emaFast = self::ema($closes, $fast);
        $emaSlow = self::ema($closes, $slow);
        $macd = [];
        $hist = [];
        $sig = [];
        for ($i = 0; $i < count($closes); $i++) {
            $macd[$i] = ($emaFast[$i] ?? null) - ($emaSlow[$i] ?? null);
        }
        $sig = self::ema(array_map(fn ($v) => (float) ($v ?: 0), $macd), $signal);
        for ($i = 0; $i < count($closes); $i++) {
            $hist[$i] = ($macd[$i] ?? 0) - ($sig[$i] ?? 0);
        }

        return ['macd' => $macd, 'signal' => $sig, 'hist' => $hist];
    }

    /**
     * @param  array<int, float>  $highs
     * @param  array<int, float>  $lows
     * @param  array<int, float>  $closes
     * @return array<int, float>
     */
    public static function atr(array $highs, array $lows, array $closes, int $period = 14): array
    {
        $tr = [];
        for ($i = 0; $i < count($closes); $i++) {
            if ($i == 0) {
                $tr[$i] = ($highs[$i] - $lows[$i]);

                continue;
            }
            $tr[$i] = max($highs[$i] - $lows[$i], abs($highs[$i] - $closes[$i - 1]), abs($lows[$i] - $closes[$i - 1]));
        }
        $atr = [];
        $prev = null;
        foreach ($tr as $i => $v) {
            if ($i == 0) {
                $atr[$i] = null;

                continue;
            }
            if ($i < $period) {
                $atr[$i] = null;

                continue;
            }
            if ($prev === null) {
                $sum = 0;
                for ($j = $i - $period + 1; $j <= $i; $j++) {
                    $sum += $tr[$j];
                }
                $prev = $sum / $period;
            } else {
                $prev = ($prev * ($period - 1) + $tr[$i]) / $period;
            }
            $atr[$i] = $prev;
        }

        return array_map(fn ($v) => (float) ($v ?? 0), $atr);
    }

    /**
     * @param  array<int, float>  $closes
     * @return array<string, array<int, float>>
     */
    public static function bollinger(array $closes, int $period = 20, float $mult = 2.0): array
    {
        $ma = [];
        $std = [];
        $up = [];
        $dn = [];
        for ($i = 0; $i < count($closes); $i++) {
            if ($i + 1 < $period) {
                $ma[$i] = $std[$i] = $up[$i] = $dn[$i] = null;

                continue;
            }
            $slice = array_slice($closes, $i - $period + 1, $period);
            $m = array_sum($slice) / $period;
            $var = 0;
            foreach ($slice as $v) {
                $var += ($v - $m) * ($v - $m);
            }
            $s = sqrt($var / $period);
            $ma[$i] = $m;
            $std[$i] = $s;
            $up[$i] = $m + $mult * $s;
            $dn[$i] = $m - $mult * $s;
        }

        return ['mid' => $ma, 'upper' => $up, 'lower' => $dn];
    }

    /**
     * @param  array<int, float>  $highs
     * @param  array<int, float>  $lows
     * @param  array<int, float>  $closes
     * @return array<string, array<int, float>>
     */
    public static function keltner(
        array $highs,
        array $lows,
        array $closes,
        int $emaPeriod = 20,
        int $atrPeriod = 10,
        float $mult = 1.5
    ): array {
        $ema = self::ema($closes, $emaPeriod);
        $atr = self::atr($highs, $lows, $closes, $atrPeriod);
        $up = $dn = [];
        for ($i = 0; $i < count($closes); $i++) {
            $up[$i] = isset($ema[$i], $atr[$i]) ? $ema[$i] + $mult * $atr[$i] : null;
            $dn[$i] = isset($ema[$i], $atr[$i]) ? $ema[$i] - $mult * $atr[$i] : null;
        }

        return ['mid' => $ema, 'upper' => $up, 'lower' => $dn];
    }

    public static function supertrend(
        array $highs,
        array $lows,
        array $closes,
        int $period = 10,
        float $mult = 3.0
    ): array {
        $atr = self::atr($highs, $lows, $closes, $period);
        $basicU = $basicL = $finalU = $finalL = $trend = [];
        for ($i = 0; $i < count($closes); $i++) {
            if (! isset($atr[$i])) {
                $finalU[$i] = $finalL[$i] = $trend[$i] = null;

                continue;
            }
            $m = ($highs[$i] + $lows[$i]) / 2;
            $basicU[$i] = $m + $mult * $atr[$i];
            $basicL[$i] = $m - $mult * $atr[$i];

            if ($i == 0 || ! isset($finalU[$i - 1])) {
                $finalU[$i] = $basicU[$i];
                $finalL[$i] = $basicL[$i];
                $trend[$i] = null;

                continue;
            }

            $finalU[$i] = ($basicU[$i] < $finalU[$i - 1] || $closes[$i - 1] > $finalU[$i - 1])
                ? $basicU[$i]
                : $finalU[$i - 1];
            $finalL[$i] = ($basicL[$i] > $finalL[$i - 1] || $closes[$i - 1] < $finalL[$i - 1])
                ? $basicL[$i]
                : $finalL[$i - 1];
            $trend[$i] = ($closes[$i] > $finalU[$i - 1])
                ? 1
                : (($closes[$i] < $finalL[$i - 1]) ? -1 : ($trend[$i - 1] ?? 0));
        }

        return ['upper' => $finalU, 'lower' => $finalL, 'trend' => $trend];
    }

    public static function vwap(array $highs, array $lows, array $closes, array $vols): array
    {
        $cumPV = 0;
        $cumV = 0;
        $vwap = [];
        for ($i = 0; $i < count($closes); $i++) {
            $tp = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $cumPV += $tp * $vols[$i];
            $cumV += $vols[$i];
            $vwap[$i] = $cumV > 0 ? $cumPV / $cumV : null;
        }

        return $vwap;
    }
}
