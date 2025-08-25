<?php

declare(strict_types=1);

namespace App\Services\Trading;

class PositionSizer
{
    public function __construct(
        private float $atrK = 1.5,
        private ?\App\Services\Trading\Contracts\ImCapServiceInterface $imCapService = null
    ) {
        $this->imCapService = $imCapService ?? app(ImCapService::class);
    }

    /**
     * Esnek imza: Test iki farklı prototip kullanabiliyor.
     * Döndürülen yapı: ['qty'=>float, 'leverage'=>float]
     */
    public function sizeByRisk(mixed ...$args): array
    {
        // Prototip-A: (side, entry, stop, equity, leverage, riskPct, qtyStep, minQty, _)
        if (isset($args[0]) && is_string($args[0])) {
            $side = strtoupper((string) $args[0]);
            $entry = (float) ($args[1] ?? 0.0);
            $stop = (float) ($args[2] ?? 0.0);
            $equity = (float) ($args[3] ?? 0.0);
            $lev = max(1.0, (float) ($args[4] ?? 1.0));
            $riskPct = (float) ($args[5] ?? 1.0);
            $qtyStep = max(0.0, (float) ($args[6] ?? 0.0));
            $minQty = max(0.0, (float) ($args[7] ?? 0.0));

            $unitRisk = $this->calculateSafeUnitRisk($entry, $stop);
            $riskAmt = max(0.0, $equity * $riskPct);
            $qty = $this->safeDivision($riskAmt, $unitRisk);

            // Qty step ve min qty kontrolü
            if ($qtyStep > 0.0) {
                $qty = floor($qty / $qtyStep) * $qtyStep;
            }
            if ($minQty > 0.0 && $qty < $minQty) {
                $qty = $minQty;
            }

            // Overflow protection ve qty clamp
            $qty = $this->applyQtyClamps($qty, $equity, $side);

            return ['qty' => max(0.0, round($qty, $this->getQtyPrecision())), 'leverage' => $lev];
        }

        // Prototip-B: (equity, riskPct, atr, price, leverage, qtyStep=0, minQty=0)
        $equity = (float) ($args[0] ?? 0.0);
        $riskPct = (float) ($args[1] ?? 0.0);
        $atr = (float) ($args[2] ?? 0.0);
        $price = (float) ($args[3] ?? 0.0);
        $lev = max(1.0, (float) ($args[4] ?? 1.0));
        $qtyStep = max(0.0, (float) ($args[5] ?? 0.0));
        $minQty = max(0.0, (float) ($args[6] ?? 0.0));

        $unitRisk = $this->calculateSafeUnitRisk($atr, $price);
        $qty = $this->safeDivision($equity * max(0.0, $riskPct) / 100.0, $unitRisk);

        // Qty step ve min qty kontrolü
        if ($qtyStep > 0.0) {
            $qty = floor($qty / $qtyStep) * $qtyStep;
        }
        if ($minQty > 0.0 && $qty < $minQty) {
            $qty = $minQty;
        }

        // Overflow protection ve qty clamp
        $qty = $this->applyQtyClamps($qty, $equity, 'LONG');

        return ['qty' => max(0.0, round($qty, $this->getQtyPrecision())), 'leverage' => $lev];
    }

    /**
     * Güvenli unit risk hesaplama
     */
    private function calculateSafeUnitRisk(float $value1, float $value2): float
    {
        $minThreshold = config('trading.risk.position_sizing.min_unit_risk_threshold', 0.0001);

        if (config('trading.risk.position_sizing.safe_division_enabled', true)) {
            $unitRisk = abs($value1 - $value2);

            return max($minThreshold, $unitRisk);
        }

        // Legacy calculation
        return max($minThreshold, abs($value1 - $value2));
    }

    /**
     * Güvenli division (division by zero koruması)
     */
    private function safeDivision(float $numerator, float $denominator): float
    {
        if (config('trading.risk.position_sizing.safe_division_enabled', true)) {
            if ($denominator <= 0.0) {
                \Log::warning('PositionSizer: Division by zero or negative denominator prevented', [
                    'numerator' => $numerator,
                    'denominator' => $denominator,
                    'fallback' => 0.0,
                ]);

                return 0.0;
            }

            return $numerator / $denominator;
        }

        // Legacy division
        return $numerator / max($denominator, 1e-9);
    }

    /**
     * Qty overflow protection ve clamp uygulama
     */
    private function applyQtyClamps(float $qty, float $equity, string $side): float
    {
        if (! config('trading.risk.position_sizing.overflow_protection', true)) {
            return $qty;
        }

        $maxQtyMultiplier = $this->getMaxQtyMultiplier($side);
        $maxQtyAbsolute = config('trading.risk.position_sizing.max_qty_absolute', 1000000);

        // Equity-based clamp
        $maxQtyByEquity = $equity * $maxQtyMultiplier;

        // Absolute clamp
        $maxQty = min($maxQtyByEquity, $maxQtyAbsolute);

        if ($qty > $maxQty) {
            \Log::warning('PositionSizer: Qty overflow prevented', [
                'original_qty' => $qty,
                'max_qty' => $maxQty,
                'equity' => $equity,
                'multiplier' => $maxQtyMultiplier,
                'side' => $side,
            ]);
            $qty = $maxQty;
        }

        return $qty;
    }

    /**
     * Side'a göre max qty multiplier al
     */
    private function getMaxQtyMultiplier(string $side): float
    {
        $defaultMultiplier = config('trading.risk.position_sizing.max_qty_multiplier', 10.0);

        // Side-specific multiplier (gelecekte eklenebilir)
        if ($side === 'SHORT') {
            // Short pozisyonlar için daha düşük multiplier
            return $defaultMultiplier * 0.8;
        }

        return $defaultMultiplier;
    }

    /**
     * Qty precision al
     */
    private function getQtyPrecision(): int
    {
        return (int) config('trading.risk.position_sizing.qty_precision', 8);
    }

    /**
     * Güvenlik parametrelerini kontrol et
     */
    public function validateSafetyParameters(): array
    {
        $config = config('trading.risk.position_sizing', []);

        $validation = [
            'max_qty_multiplier' => [
                'value' => $config['max_qty_multiplier'] ?? 10.0,
                'valid' => ($config['max_qty_multiplier'] ?? 10.0) > 0.0,
                'message' => 'Max qty multiplier must be positive',
            ],
            'min_unit_risk_threshold' => [
                'value' => $config['min_unit_risk_threshold'] ?? 0.0001,
                'valid' => ($config['min_unit_risk_threshold'] ?? 0.0001) > 0.0,
                'message' => 'Min unit risk threshold must be positive',
            ],
            'max_qty_absolute' => [
                'value' => $config['max_qty_absolute'] ?? 1000000,
                'valid' => ($config['max_qty_absolute'] ?? 1000000) > 0.0,
                'message' => 'Max qty absolute must be positive',
            ],
            'safe_division_enabled' => [
                'value' => $config['safe_division_enabled'] ?? true,
                'valid' => is_bool($config['safe_division_enabled'] ?? true),
                'message' => 'Safe division enabled must be boolean',
            ],
            'overflow_protection' => [
                'value' => $config['overflow_protection'] ?? true,
                'valid' => is_bool($config['overflow_protection'] ?? true),
                'message' => 'Overflow protection must be boolean',
            ],
        ];

        $allValid = array_reduce($validation, fn ($carry, $item) => $carry && $item['valid'], true);

        return [
            'valid' => $allValid,
            'details' => $validation,
        ];
    }

    /**
     * Size position based on Initial Margin cap
     *
     * @param  float  $equity  Account equity
     * @param  float  $marginUtilization  Current margin utilization (0.0-1.0)
     * @param  float  $freeCollateral  Available free collateral
     * @param  float  $leverage  Desired leverage
     * @param  float  $price  Current price
     * @param  float  $qtyStep  Quantity step size
     * @param  float  $minQty  Minimum quantity
     * @return array{qty: float, leverage: float, im_required: float, risk_band: string}
     */
    public function sizeByImCap(
        float $equity,
        float $marginUtilization,
        float $freeCollateral,
        float $leverage,
        float $price,
        float $qtyStep = 0.001,
        float $minQty = 0.001
    ): array {
        if (! $this->imCapService) {
            return [
                'qty' => 0.0,
                'leverage' => $leverage,
                'im_required' => 0.0,
                'risk_band' => 'extreme',
            ];
        }

        $result = $this->imCapService->calculatePositionSize(
            $equity,
            $marginUtilization,
            $freeCollateral,
            $leverage,
            $price
        );

        $qty = $result['qty'];

        // Apply quantity step and minimum quantity constraints
        if ($qtyStep > 0.0) {
            $qty = floor($qty / $qtyStep) * $qtyStep;
        }

        if ($minQty > 0.0 && $qty < $minQty) {
            $qty = $minQty;
        }

        // Apply safety clamps
        $qty = $this->applyQtyClamps($qty, $equity, 'LONG'); // Default to LONG for safety

        return [
            'qty' => max(0.0, round($qty, $this->getQtyPrecision())),
            'leverage' => $leverage,
            'im_required' => $result['im_required'] ?? 0.0,
            'risk_band' => $result['risk_band'] ?? 'MEDIUM_RISK',
            'band' => $this->normalizeRiskBand($result['risk_band'] ?? 'MEDIUM_RISK'), // Backward compatibility
        ];
    }

    /**
     * Normalize risk band to expected format
     */
    private function normalizeRiskBand(string $band): string
    {
        return match (strtolower($band)) {
            'low', 'low_risk' => 'LOW_RISK',
            'medium', 'medium_risk' => 'MEDIUM_RISK',
            'high', 'high_risk', 'extreme' => 'HIGH_RISK',
            default => 'MEDIUM_RISK',
        };
    }
}
