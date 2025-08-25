<?php

declare(strict_types=1);

namespace App\Services\Lab;

/**
 * Slippage (bps) + fee (bps) → net PnL% (yüzde). Leg bazında hesaplanır;
 * toplam PnL, leg oranlarıyla ağırlıklandırılır.
 */
final class ExecutionCostModel
{
    /**
     * @param  'LONG'|'SHORT'  $side
     *                                $cfg ör.: [
     *                                'slippage_bps' => ['entry'=>2.0,'exit'=>2.0],
     *                                'fee_bps'      => ['entry'=>5.0,'exit'=>5.0],
     *                                ]
     */
    public function netPnlPct(string $side, float $entry, float $exit, array $cfg): float
    {
        $side = strtoupper($side);
        $slipIn = (float) ($cfg['slippage_bps']['entry'] ?? 0.0) / 10000.0;
        $slipOut = (float) ($cfg['slippage_bps']['exit'] ?? 0.0) / 10000.0;
        $feeIn = (float) ($cfg['fee_bps']['entry'] ?? 0.0) / 10000.0;
        $feeOut = (float) ($cfg['fee_bps']['exit'] ?? 0.0) / 10000.0;

        if ($side === 'LONG') {
            $entry *= (1.0 + $slipIn);
            $exit *= (1.0 - $slipOut);
        } else {
            $entry *= (1.0 - $slipIn);
            $exit *= (1.0 + $slipOut);
        }

        $gross = $side === 'LONG'
            ? (($exit - $entry) / $entry)
            : (($entry - $exit) / $entry);

        $feePct = ($feeIn + $feeOut);
        $net = $gross - $feePct;

        return $net * 100.0; // yüzde
    }
}
