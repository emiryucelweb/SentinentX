<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Services\Trading\ImCapService;
use Illuminate\Support\Facades\Log;

final class ImBandGuard
{
    public function __construct(
        private ImCapService $imCapService
    ) {}

    public function checkImBandViolation(
        string $symbol,
        float $currentIm,
        float $requestedLeverage
    ): array {
        $imCapData = $this->imCapService->calculateImCap($symbol, $currentIm);

        $maxLeverage = $imCapData['leverage_clamp']['max'] ?? 1.0;
        $isViolation = $requestedLeverage > $maxLeverage;

        if ($isViolation) {
            Log::warning('IM Band violation detected', [
                'symbol' => $symbol,
                'current_im' => $currentIm,
                'requested_leverage' => $requestedLeverage,
                'max_allowed_leverage' => $maxLeverage,
                'band' => $imCapData['band'],
            ]);
        }

        return [
            'is_violation' => $isViolation,
            'max_allowed_leverage' => $maxLeverage,
            'band' => $imCapData['band'],
            'current_im' => $currentIm,
            'requested_leverage' => $requestedLeverage,
        ];
    }
}
