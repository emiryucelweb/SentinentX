<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\Trading\PnlServiceInterface;
use App\Models\Trade;
use App\Services\Exchange\BybitClient;
use Illuminate\Support\Facades\Log;

class PnlService implements PnlServiceInterface
{
    public function __construct(private BybitClient $bybit) {}

    public function computeAndPersist(Trade $trade, string $closeOrderLinkId): array
    {
        $symbol = $trade->symbol;
        $start = ($trade->decided_at ?: $trade->created_at)
            ? ($trade->decided_at ?: $trade->created_at)->getTimestampMs()
            : now()->subMinutes(60)->getTimestampMs();
        $end = now()->getTimestampMs();

        $execs = (array) data_get($this->bybit->executionList($symbol, $start, $end), 'result.list', []);
        if (! $execs) {
            Log::warning('executionList empty', compact('symbol', 'start', 'end'));
        }

        // BCMath precision for financial calculations
        $precision = 8;
        $feesTotal = '0';
        foreach ($execs as $ex) {
            $execFee = (string) ($ex['execFee'] ?? '0');
            $feesTotal = bcadd($feesTotal, $execFee, $precision);
        }

        $closeQty = '0';
        $closeNotional = '0';
        foreach ($execs as $ex) {
            if (($ex['orderLinkId'] ?? null) === $closeOrderLinkId) {
                $p = (string) ($ex['execPrice'] ?? '0');
                $q = (string) ($ex['execQty'] ?? '0');
                if (bccomp($p, '0', $precision) > 0 && bccomp($q, '0', $precision) > 0) {
                    $closeQty = bcadd($closeQty, $q, $precision);
                    $closeNotional = bcadd($closeNotional, bcmul($p, $q, $precision), $precision);
                }
            }
        }
        $avgClose = bccomp($closeQty, '0', $precision) > 0 ? bcdiv($closeNotional, $closeQty, $precision) : null;

        $entry = (string) ($trade->entry_price ?? '0');
        $qty = (string) ($trade->qty ?? '0');
        $gross = '0';

        if ($avgClose !== null && bccomp($entry, '0', $precision) > 0 && bccomp($qty, '0', $precision) > 0) {
            if ($trade->side === 'LONG') {
                $priceDiff = bcsub($avgClose, $entry, $precision);
                $gross = bcmul($priceDiff, $qty, $precision);
            } else {
                $priceDiff = bcsub($entry, $avgClose, $precision);
                $gross = bcmul($priceDiff, $qty, $precision);
            }
        }

        $net = bcsub($gross, $feesTotal, $precision);

        $trade->pnl = (float) $net; // Model için float'a çevir
        $trade->save();

        $basis = bcmul($entry, $qty, $precision);
        $basis = bccomp($basis, '0', $precision) === 0 ? '0.00000001' : $basis; // Sıfır bölme koruması
        $pnlPct = bcmul(bcdiv($net, $basis, $precision), '100', 2);

        return [
            'avg_close' => $avgClose ? (float) $avgClose : null,
            'fees_total' => (float) $feesTotal,
            'gross' => (float) $gross,
            'net' => (float) $net,
            'pnl_pct' => (float) $pnlPct,
        ];
    }
}
