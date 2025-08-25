<?php

declare(strict_types=1);

namespace App\Services\Lab;

use App\Contracts\Lab\MetricsServiceInterface;
use App\Models\LabTrade;
use Carbon\CarbonImmutable;

class MetricsService implements MetricsServiceInterface
{
    /**
     * Gün sonu metrikleri (NET) + GROSS ekler.
     * Dönüş:
     *  - pf (net), pf_gross, maxdd_pct (net), sharpe (net), trades,
     *  - avg_trade_net_pct, avg_trade_gross_pct
     */
    public function computeDaily(CarbonImmutable $day, float $initialEquity): array
    {
        $date = $day->toDateString();
        $trades = LabTrade::query()
            ->whereDate('closed_at', $date)
            ->orderBy('closed_at')
            ->get();

        $n = $trades->count();
        if ($n === 0) {
            return [
                'pf' => 1.0,
                'pf_gross' => 1.0,
                'maxdd_pct' => 0.0,
                'sharpe' => null,
                'trades' => 0,
                'avg_trade_net_pct' => 0.0,
                'avg_trade_gross_pct' => 0.0,
            ];
        }

        $equityNet = $initialEquity;
        $equityGross = $initialEquity;
        $peak = $initialEquity;
        $maxDdPct = 0.0;
        $rets = [];
        $grossRets = [];

        foreach ($trades as $t) {
            $netPct = (float) ($t->pnl_pct ?? 0.0);
            $grossPct = $this->grossTradePct($t);

            $rets[] = $netPct / 100.0;
            $grossRets[] = $grossPct / 100.0;

            $equityNet *= (1.0 + $netPct / 100.0);
            $equityGross *= (1.0 + $grossPct / 100.0);

            if ($equityNet > $peak) {
                $peak = $equityNet;
            }
            $dd = ($peak > 0.0) ? (($peak - $equityNet) / $peak) * 100.0 : 0.0;
            if ($dd > $maxDdPct) {
                $maxDdPct = $dd;
            }
        }

        $pfNet = $equityNet / $initialEquity;
        $pfGross = $equityGross / $initialEquity;

        $sharpe = $this->sharpe($rets);

        return [
            'pf' => round($pfNet, 6),
            'pf_gross' => round($pfGross, 6),
            'maxdd_pct' => round($maxDdPct, 4),
            'sharpe' => $sharpe,
            'trades' => $n,
            'avg_trade_net_pct' => round(array_sum(array_map(fn ($r) => $r * 100.0, $rets)) / $n, 4),
            'avg_trade_gross_pct' => round(array_sum(array_map(fn ($r) => $r * 100.0, $grossRets)) / $n, 4),
        ];
    }

    private function sharpe(array $rets): ?float
    {
        $n = count($rets);
        if ($n === 0) {
            return null;
        }
        $mean = array_sum($rets) / $n;
        $var = 0.0;
        foreach ($rets as $r) {
            $var += ($r - $mean) ** 2;
        } $var /= $n; // pop var
        $std = sqrt($var);
        if ($std == 0.0) {
            return null;
        }
        $sh = $mean / $std * sqrt($n); // trade-periyodu

        return round($sh, 6);
    }

    private function grossTradePct(\App\Models\LabTrade $t): float
    {
        $entry = (float) $t->entry_price;
        $side = strtoupper((string) $t->side);
        $meta = (array) ($t->meta ?? []);
        $partials = (array) ($meta['partials'] ?? []);

        if (! empty($partials)) {
            $sum = 0.0;
            $w = 0.0;
            foreach ($partials as $leg) {
                $frac = (float) ($leg['frac'] ?? 1.0);
                $exit = (float) ($leg['exit'] ?? $entry);
                $gross = ($side === 'LONG') ? (($exit - $entry) / $entry * 100.0) : (($entry - $exit) / $entry * 100.0);
                $sum += $frac * $gross;
                $w += $frac;
            }

            return $w > 0 ? $sum : 0.0;
        }

        $exitPrice = $t->exit_price;
        if ($exitPrice !== null) {
            $exit = (float) $exitPrice;

            return ($side === 'LONG') ? (($exit - $entry) / $entry * 100.0) : (($entry - $exit) / $entry * 100.0);
        }

        return (float) ($t->pnl_pct ?? 0.0);
    }
}
