<?php

declare(strict_types=1);

namespace App\Services\Trading\Contracts;

interface ImCapServiceInterface
{
    /**
     * Calculate position size based on Initial Margin cap
     *
     * @param  float  $equity  Account equity
     * @param  float  $marginUtilization  Current margin utilization (0.0-1.0)
     * @param  float  $freeCollateral  Available free collateral
     * @param  float  $leverage  Desired leverage
     * @param  float  $price  Current price
     * @return array{qty: float, im_required: float, risk_band: string}
     */
    public function calculatePositionSize(
        float $equity,
        float $marginUtilization,
        float $freeCollateral,
        float $leverage,
        float $price
    ): array;
}
