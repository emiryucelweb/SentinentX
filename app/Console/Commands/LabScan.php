<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Contracts\Notifier\AlertDispatcher;
use App\Models\LabTrade;
use App\Services\AI\ConsensusService;
use App\Services\Lab\ExecutionCostModel;
use App\Services\Lab\PathSimulator;
use App\Services\Trading\StopCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class LabScan extends Command
{
    protected $signature = 'sentx:lab-scan '
        .'{--symbol= : Tek sembol} '
        .'{--count=3 : Üretilecek trade adedi} '
        .'{--atrK=2.0 : SL/TP için ATR-k} '
        .'{--qty=0.01 : İşlem miktarı} '
        .'{--seed= : Deterministik simülasyon için tohum}';

    protected $description = 'Consensus sinyali alır; first-touch + (opsiyonel) partial‑TP ile '
        .'patika simülasyonu yapar; fee/slippage sonrası NET ve GROSS PnL% kaydeder.';

    public function handle(
        ConsensusService $consensus,
        ExchangeClientInterface $exchange,
        StopCalculator $stops,
        PathSimulator $path,
        ExecutionCostModel $costs,
        AlertDispatcher $alerts
    ): int {
        // LAB scan enabled kontrolü
        if (! (bool) config('lab.scan.enabled', true)) {
            $this->info('LAB scan disabled.');

            return self::SUCCESS;
        }

        $testMode = (bool) (config('lab.simulation.test_mode', config('lab.test_mode', false)));
        $symbol = strtoupper((string) ($this->option('symbol')
            ?: (config('lab.symbols.0') ?? 'BTCUSDT')));
        $count = max(1, (int) ($this->option('count') ?: 3));
        $atrK = (float) ($this->option('atrK') ?: 2.0);
        $qty = (float) ($this->option('qty') ?: 0.01);
        $seedOpt = $this->option('seed');
        if ($seedOpt !== null) {
            mt_srand((int) $seedOpt);
        }

        $bias = (float) config('lab.path.bar_touch_bias', 0.5);
        $maxBars = (int) config('lab.path.max_bars', 60);
        $volPct = (float) config('lab.path.synthetic.vol_pct', 0.004);
        $driftPct = (float) config('lab.path.synthetic.drift_pct', 0.0);
        $interval = (int) config('lab.path.bar_interval_min', 5);
        $category = (string) config('lab.simulation.category', 'linear');

        // Costs & partials
        $partialsEnabled = (bool) config('lab.partials.enabled', false);
        $tp1Frac = (float) config('lab.partials.tp1_frac', 0.5);
        $tp2RR = (float) config('lab.partials.tp2_rr', 2.0);
        $beAfter = (bool) config('lab.partials.move_sl_to_be', true);

        $feeMode = (string) config('lab.costs.mode', 'taker');

        // Default maliyetler
        $feeBps = [
            'entry' => (float) ($feeMode === 'maker'
                ? config('lab.costs.maker_fee_bps', 1.0)
                : config('lab.costs.taker_fee_bps', 5.0)),
            'exit' => (float) ($feeMode === 'maker'
                ? config('lab.costs.maker_fee_bps', 1.0)
                : config('lab.costs.taker_fee_bps', 5.0)),
        ];
        $slipBps = [
            'entry' => (float) config('lab.costs.slippage_bps.entry', 2.0),
            'exit' => (float) config('lab.costs.slippage_bps.exit', 2.0),
        ];

        // Sembol-bazlı maliyet override'ları
        $symbolCosts = config("lab.costs.symbols.{$symbol}", []);
        if (! empty($symbolCosts)) {
            if (isset($symbolCosts['taker_fee_bps'])) {
                $feeBps['entry'] = $feeBps['exit'] = (float) $symbolCosts['taker_fee_bps'];
            }
            if (isset($symbolCosts['slippage_bps'])) {
                $slipBps = array_merge($slipBps, $symbolCosts['slippage_bps']);
            }
        }
        $alertTrades = (bool) data_get(config('lab.simulation'), 'alerts.trade_events', true);

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            // 1) Fiyat
            $price = 0.0;
            if (! $testMode) {
                $tick = $exchange->tickers($symbol, $category);
                $price = (float) (Arr::get($tick, 'result.list.0.lastPrice')
                    ?? Arr::get($tick, 'result.list.0.last_price')
                    ?? Arr::get($tick, 'lastPrice') ?? 0.0);
            }
            if ($price <= 0.0) {
                $price = (float) config('lab.simulation.base_price', 30000.0) + (mt_rand(-200, 200));
            }

            // 2) Consensus / test mode sinyali
            $cycleUuid = (string) Str::uuid();
            $payload = [
                'symbol' => $symbol,
                'price' => $price,
                'cycle_uuid' => $cycleUuid,
            ];
            $decision = $testMode
                ? ['action' => (mt_rand(0, 1) ? 'LONG' : 'SHORT'), 'confidence' => mt_rand(55, 85)]
                : $consensus->decide($payload);

            $finalAction = strtoupper((string) (
                data_get($decision, 'final.action')
                ?? data_get($decision, 'final_decision')
                ?? data_get($decision, 'action')
                ?? 'HOLD'
            ));
            $confidence = (float) (data_get($decision, 'final.confidence')
                ?? data_get($decision, 'final_confidence')
                ?? data_get($decision, 'confidence')
                ?? 60.0);

            if (! in_array($finalAction, ['LONG', 'SHORT'], true)) {
                continue; // trade oluşturma
            }

            // 3) Stop/TP
            $stopLevels = $this->computeStopsSafe($stops, $symbol, $finalAction, $price, $atrK);
            $sl = $stopLevels[0];
            $tp1 = $stopLevels[1];

            // 4) Bar patikası
            $bars = $testMode
                ? $path->synthesize(
                    $price,
                    $maxBars,
                    $volPct,
                    $driftPct,
                    seed: $seedOpt ? (int) $seedOpt : null,
                    intervalMin: $interval
                )
                : $path->toBarsFromBybit(
                    $exchange->kline(
                        $symbol,
                        (string) max(1, (int) ($interval / 1)),
                        $maxBars,
                        $category
                    )
                );

            $meta = [
                'source' => $testMode ? 'synthetic' : 'kline',
                'confidence' => $confidence,
                'atr_k' => $atrK,
                'tp1' => $tp1,
                'sl' => $sl,
                'partials' => null,
                'bars_checked' => null,
            ];

            $costCfg = ['slippage_bps' => $slipBps, 'fee_bps' => $feeBps];

            // 5) First-touch + (opsiyonel) Partial‑TP
            $netPnlPct = 0.0;
            $grossPnlPct = 0.0;
            $closedAt = now();
            $barsUsedTotal = 0;
            $partialMeta = [];
            if ($partialsEnabled) {
                // 5.a) İlk yarış: SL vs TP1
                $res1 = $path->firstTouch($finalAction, $price, $sl, $tp1, $bars, $bias);
                $barsUsedTotal += (int) $res1['bars'];
                $closedAt = now()->addMinutes($interval * $barsUsedTotal);

                if (str_starts_with((string) $res1['reason'], 'SL')) {
                    // Tamamı SL — tek leg
                    $gross1 = $this->grossPct($finalAction, $price, (float) $res1['exit']);
                    $net1 = $costs->netPnlPct($finalAction, $price, (float) $res1['exit'], $costCfg);
                    $grossPnlPct = $gross1;
                    $netPnlPct = $net1;
                    $partialMeta[] = [
                        'leg' => 'ALL', 'exit' => (float) $res1['exit'], 'reason' => $res1['reason'],
                        'frac' => 1.0, 'pnl_pct' => $net1, 'gross_pct' => $gross1,
                    ];
                } else { // TP1 geldi → kısmi realize
                    $frac1 = max(0.0, min(1.0, $tp1Frac));
                    $gross1 = $this->grossPct($finalAction, $price, (float) $res1['exit']);
                    $leg1 = $costs->netPnlPct($finalAction, $price, (float) $res1['exit'], $costCfg);

                    // Kalan için SL ayarı
                    $sl2 = $beAfter ? $price : $sl;
                    $rr = abs($price - $sl); // risk mesafesi
                    $tp2 = $finalAction === 'LONG' ? $price + $tp2RR * $rr : $price - $tp2RR * $rr;

                    // Kalan barlarla ikinci yarış
                    $barsRem = array_slice($bars, (int) $res1['bars']);
                    if (empty($barsRem)) {
                        $barsRem = [$bars[count($bars) - 1]];
                    }
                    $res2 = $path->firstTouch($finalAction, $price, $sl2, $tp2, $barsRem, $bias);
                    $barsUsedTotal += (int) $res2['bars'];
                    $closedAt = now()->addMinutes($interval * $barsUsedTotal);

                    $gross2 = $this->grossPct($finalAction, $price, (float) $res2['exit']);
                    $leg2 = $costs->netPnlPct($finalAction, $price, (float) $res2['exit'], $costCfg);

                    $grossPnlPct = $frac1 * $gross1 + (1.0 - $frac1) * $gross2;
                    $netPnlPct = $frac1 * $leg1 + (1.0 - $frac1) * $leg2;

                    $partialMeta[] = [
                        'leg' => 'TP1', 'exit' => (float) $res1['exit'], 'reason' => $res1['reason'],
                        'frac' => $frac1, 'pnl_pct' => $leg1, 'gross_pct' => $gross1,
                    ];
                    $partialMeta[] = [
                        'leg' => 'REST', 'exit' => (float) $res2['exit'], 'reason' => $res2['reason'],
                        'frac' => 1.0 - $frac1, 'pnl_pct' => $leg2, 'gross_pct' => $gross2,
                        'sl_after' => $sl2, 'tp2' => $tp2,
                    ];
                }
            } else {
                // Tek bacak: SL vs TP1
                $res = $path->firstTouch($finalAction, $price, $sl, $tp1, $bars, $bias);
                $barsUsedTotal += (int) $res['bars'];
                $closedAt = now()->addMinutes($interval * $barsUsedTotal);
                $gross = $this->grossPct($finalAction, $price, (float) $res['exit']);
                $net = $costs->netPnlPct($finalAction, $price, (float) $res['exit'], $costCfg);
                $grossPnlPct = $gross;
                $netPnlPct = $net;
                $partialMeta[] = [
                    'leg' => 'ALL', 'exit' => (float) $res['exit'], 'reason' => $res['reason'],
                    'frac' => 1.0, 'pnl_pct' => $net, 'gross_pct' => $gross,
                ];
            }

            // 6) Kayıt + alert
            $meta['partials'] = $partialMeta;
            $meta['bars_checked'] = $barsUsedTotal;
            $meta['costs'] = [
                'fee_bps' => $feeBps,
                'slippage_bps' => $slipBps,
                'mode' => $feeMode,
            ];
            $meta['pnl_pct_gross'] = round($grossPnlPct, 4);

            $trade = LabTrade::create([
                'symbol' => $symbol,
                'side' => $finalAction,
                'qty' => $qty,
                'entry_price' => $price,
                'exit_price' => null,
                'opened_at' => now(),
                'closed_at' => $closedAt,
                'pnl_quote' => null,
                'pnl_pct' => round($netPnlPct, 4), // NET
                'cycle_uuid' => $cycleUuid,
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);

            if ($alertTrades) {
                $alerts->send('info', 'LAB_TRADE_OPENED', 'LAB trade opened', [
                    'symbol' => $symbol, 'side' => $finalAction, 'entry' => $price,
                    'qty' => $qty, 'cycle_uuid' => $cycleUuid,
                ], dedupKey: 'lab-open-'.$trade->id);
                $alerts->send('info', 'LAB_TRADE_CLOSED', 'LAB trade closed', [
                    'symbol' => $symbol, 'side' => $finalAction, 'entry' => $price,
                    'pnl_pct' => round($netPnlPct, 4), 'cycle_uuid' => $cycleUuid,
                    'pnl_pct_gross' => round($grossPnlPct, 4),
                ], dedupKey: 'lab-close-'.$trade->id);
            }

            $generated++;
        }

        $this->info("LAB scan generated trades: {$generated} symbol={$symbol}");

        return self::SUCCESS;
    }

    private function grossPct(string $side, float $entry, float $exit): float
    {
        $side = strtoupper($side);

        return ($side === 'LONG')
            ? (($exit - $entry) / $entry) * 100.0
            : (($entry - $exit) / $entry) * 100.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeStopsSafe(
        StopCalculator $stops,
        string $symbol,
        string $action,
        float $price,
        float $atrK
    ): array {
        try {
            if (method_exists($stops, 'compute')) {
                $tuple = $stops->compute($symbol, strtoupper($action), $price, $atrK);
                if (is_array($tuple) && count($tuple) >= 2) {
                    [$sl, $tp] = $tuple;

                    return [(float) $sl, (float) $tp];
                }
            }
        } catch (\Throwable) {
        }
        // Fallback — yüzdesel
        $k = max(0.1, (float) $atrK);
        if (strtoupper($action) === 'LONG') {
            $sl = $price * (1.0 - 0.005 * $k);
            $tp = $price * (1.0 + 0.01 * $k);
        } else {
            $sl = $price * (1.0 + 0.005 * $k);
            $tp = $price * (1.0 - 0.01 * $k);
        }

        return [round($sl, 2), round($tp, 2)];
    }
}
