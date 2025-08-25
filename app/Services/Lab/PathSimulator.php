<?php

declare(strict_types=1);

namespace App\Services\Lab;

use Carbon\CarbonImmutable;

/**
 * İlk temas (first-touch) motoru: Bar bar ilerleyip TP/SL’ye önce hangisinin
 * değildiğini belirler. Eğer ikisi de aynı barda mümkünse, bias ile seçim yapar.
 */
final class PathSimulator
{
    /**
     * @param  'LONG'|'SHORT'  $side
     * @param  array<int, array{ts:int, o:float, h:float, l:float, c:float}>  $bars
     * @return array{exit:float, reason:string, bars:int, closed_at:int}
     */
    public function firstTouch(string $side, float $entry, float $sl, float $tp, array $bars, float $bias = 0.5): array
    {
        $side = strtoupper($side);
        $bias = max(0.0, min(1.0, $bias));
        $n = 0;
        $exit = $entry;
        $reason = 'TIMEOUT';
        $closedAt = time();

        foreach ($bars as $bar) {
            $n++;
            $o = (float) $bar['o'];
            $h = (float) $bar['h'];
            $l = (float) $bar['l'];
            $c = (float) $bar['c'];
            $ts = (int) $bar['ts'];
            $closedAt = $ts;

            // Açılışta gap
            if ($side === 'LONG') {
                if ($o >= $tp) {
                    $exit = $tp;
                    $reason = 'TP_GAP';
                    break;
                }
                if ($o <= $sl) {
                    $exit = $sl;
                    $reason = 'SL_GAP';
                    break;
                }
            } else { // SHORT
                if ($o <= $tp) {
                    $exit = $tp;
                    $reason = 'TP_GAP';
                    break;
                }
                if ($o >= $sl) {
                    $exit = $sl;
                    $reason = 'SL_GAP';
                    break;
                }
            }

            // Bar içi dokunuşlar
            $tpHit = false;
            $slHit = false;
            if ($side === 'LONG') {
                if ($h >= $tp) {
                    $tpHit = true;
                }
                if ($l <= $sl) {
                    $slHit = true;
                }
            } else { // SHORT
                if ($l <= $tp) {
                    $tpHit = true;
                }
                if ($h >= $sl) {
                    $slHit = true;
                }
            }

            if ($tpHit && $slHit) {
                // Aynı barda ikisi de mümkün — bias ile sırayı seç
                $pickTp = (mt_rand(0, 1000) / 1000.0) <= $bias;
                if ($pickTp) {
                    $exit = $tp;
                    $reason = 'TP_TOUCH';
                } else {
                    $exit = $sl;
                    $reason = 'SL_TOUCH';
                }
                break;
            }
            if ($tpHit) {
                $exit = $tp;
                $reason = 'TP_TOUCH';
                break;
            }
            if ($slHit) {
                $exit = $sl;
                $reason = 'SL_TOUCH';
                break;
            }
        }

        return ['exit' => $exit, 'reason' => $reason, 'bars' => $n, 'closed_at' => $closedAt];
    }

    /**
     * Bybit kline çıktısını bar dizisine dönüştürür.
     *
     * @param  array  $klineResponse  Bybit benzeri: result.list = [[start, open, high, low, close, ...], ...]
     * @return array<int, array{ts:int, o:float, h:float, l:float, c:float}>
     */
    public function toBarsFromBybit(array $klineResponse): array
    {
        $rows = $klineResponse['result']['list'] ?? [];
        $out = [];
        foreach ($rows as $r) {
            if (! is_array($r) || count($r) < 5) {
                continue;
            }
            $ts = (int) ($r[0] ?? time());
            $o = (float) $r[1];
            $h = (float) $r[2];
            $l = (float) $r[3];
            $c = (float) $r[4];
            $out[] = ['ts' => $ts, 'o' => $o, 'h' => $h, 'l' => $l, 'c' => $c];
        }

        return $out;
    }

    /**
     * Sentetik bar üretir (LAB test modu için). Basit rastgele yürüyüş.
     *
     * @return array<int, array{ts:int, o:float, h:float, l:float, c:float}>
     */
    public function synthesize(
        float $startPrice,
        int $maxBars = 60,
        float $volPct = 0.004,
        float $driftPct = 0.0,
        ?int $seed = null,
        int $intervalMin = 5
    ): array {
        if ($seed !== null) {
            mt_srand($seed);
        }
        $bars = [];
        $now = CarbonImmutable::now();
        $prev = $startPrice;
        for ($i = 0; $i < $maxBars; $i++) {
            $o = $prev;
            $r = ($driftPct) + ((mt_rand(0, 1000) / 1000.0) * 2 * $volPct - $volPct);
            // [-vol, +vol] + drift
            $c = $o * (1.0 + $r);
            $range = max(abs($c - $o), $o * ($volPct * 0.2));
            $h = max($o, $c) + $range;
            $l = min($o, $c) - $range;
            $ts = $now->addMinutes($intervalMin * ($i + 1))->getTimestamp();
            $bars[] = ['ts' => $ts, 'o' => $o, 'h' => $h, 'l' => $l, 'c' => $c];
            $prev = $c;
        }

        return $bars;
    }
}
