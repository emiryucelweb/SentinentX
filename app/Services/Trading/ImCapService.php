<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Trading\Contracts\ImCapServiceInterface;

class ImCapService implements ImCapServiceInterface
{
    /**
     * Calculate IM cap based on equity, margin utilization and free collateral
     *
     * @return array{im_cap: float, risk_band: string, max_leverage: float}
     */
    public function calculateImCap(float $equity, float $marginUtilization, float $freeCollateral): array
    {
        // Risk bantlarına göre IM cap hesaplama
        $riskBand = $this->determineRiskBand($marginUtilization);

        $imCapMultiplier = match ($riskBand) {
            'low' => 0.15,      // %15 IM cap
            'medium' => 0.10,   // %10 IM cap
            'high' => 0.05,     // %5 IM cap
            'extreme' => 0.02,  // %2 IM cap
            default => 0.10
        };

        $maxLeverage = match ($riskBand) {
            'low' => 25.0,
            'medium' => 15.0,
            'high' => 10.0,
            'extreme' => 5.0,
            default => 10.0
        };

        // Available collateral'in bir kısmını IM cap olarak kullan
        $availableForIm = min($freeCollateral, $equity * 0.8); // Max %80 equity
        $imCap = $availableForIm * $imCapMultiplier;

        return [
            'im_cap' => max(0.0, $imCap),
            'risk_band' => $riskBand,
            'max_leverage' => $maxLeverage,
        ];
    }

    /**
     * Calculate notional cap based on IM cap and leverage
     */
    public function calculateNotionalCap(float $imCap, float $leverage): float
    {
        if ($imCap <= 0 || $leverage <= 0) {
            return 0.0;
        }

        // Notional = IM * Leverage
        return $imCap * $leverage;
    }

    /**
     * Calculate position size based on equity, margin utilization, free collateral, leverage and price
     *
     * @return array{qty: float, notional: float, im_required: float, risk_band: string}
     */
    public function calculatePositionSize(
        float $equity,
        float $marginUtilization,
        float $freeCollateral,
        float $leverage,
        float $price
    ): array {
        if ($price <= 0) {
            return [
                'qty' => 0.0,
                'notional' => 0.0,
                'im_required' => 0.0,
                'risk_band' => 'extreme',
            ];
        }

        $imCapResult = $this->calculateImCap($equity, $marginUtilization, $freeCollateral);
        $imCap = $imCapResult['im_cap'];
        $riskBand = $imCapResult['risk_band'];
        $maxLev = $imCapResult['max_leverage'];

        // Leverage'ı risk bandına göre sınırla
        $effectiveLeverage = min($leverage, $maxLev);

        // Notional cap hesapla
        $notionalCap = $this->calculateNotionalCap($imCap, $effectiveLeverage);

        // Quantity hesapla
        $qty = $notionalCap > 0 ? $notionalCap / $price : 0.0;

        // IM requirement hesapla
        $imRequired = $notionalCap / $effectiveLeverage;

        return [
            'qty' => max(0.0, $qty),
            'notional' => $notionalCap,
            'im_required' => $imRequired,
            'risk_band' => $riskBand,
        ];
    }

    /**
     * Determine risk band based on margin utilization
     */
    private function determineRiskBand(float $marginUtilization): string
    {
        if ($marginUtilization < 0.3) {
            return 'low';
        } elseif ($marginUtilization < 0.6) {
            return 'medium';
        } elseif ($marginUtilization < 0.8) {
            return 'high';
        } else {
            return 'extreme';
        }
    }
}
