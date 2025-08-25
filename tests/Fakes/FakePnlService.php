<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Trading\PnlServiceInterface;
use App\Models\Trade;

class FakePnlService implements PnlServiceInterface
{
    public function computeAndPersist(Trade $trade, string $closeOrderLinkId): array
    {
        // Mock PnL hesaplama
        $entry = (float) ($trade->entry_price ?? 0);
        $qty = (float) $trade->qty;
        $side = $trade->side ?? 'LONG';

        // Basit mock PnL hesaplama
        $avgClose = $entry * ($side === 'LONG' ? 1.02 : 0.98); // %2 profit/loss
        $gross = $side === 'LONG' ? ($avgClose - $entry) * $qty : ($entry - $avgClose) * $qty;
        $feesTotal = $entry * $qty * 0.001; // %0.1 fee
        $net = $gross - $feesTotal;

        $basis = max(1e-9, $entry * $qty);
        $pnlPct = ($net / $basis) * 100.0;

        return [
            'avg_close' => $avgClose,
            'fees_total' => $feesTotal,
            'gross' => $gross,
            'net' => $net,
            'pnl_pct' => $pnlPct,
        ];
    }
}
