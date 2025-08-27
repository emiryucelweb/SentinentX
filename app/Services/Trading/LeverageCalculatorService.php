<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class LeverageCalculatorService
{
    /**
     * Risk profiline göre optimal kaldıracı hesapla
     *
     * @return array<string, mixed>
     */
    public function calculateOptimalLeverage(
        User $user,
        array $aiDecision,
        string $symbol,
        float $accountBalance
    ): array {
        $riskProfile = $this->getUserRiskProfile($user);
        $confidence = (float) ($aiDecision['confidence'] ?? 0);
        $aiSuggestedLeverage = (float) ($aiDecision['leverage'] ?? 0);

        // Risk profili kaldıraç limitleri
        $leverageConfig = $riskProfile['leverage'] ?? ['min' => 3, 'max' => 15, 'default' => 5];
        $minLeverage = (float) $leverageConfig['min'];
        $maxLeverage = (float) $leverageConfig['max'];
        $defaultLeverage = (float) $leverageConfig['default'];

        // Sembol bazlı maksimum kaldıraç kontrolü
        $symbolMaxLeverage = $this->getSymbolMaxLeverage($symbol);
        $effectiveMaxLeverage = min($maxLeverage, $symbolMaxLeverage);

        // AI önerisi geçerli mi kontrol et
        $finalLeverage = $this->determineFinalLeverage(
            $aiSuggestedLeverage,
            $confidence,
            $minLeverage,
            $effectiveMaxLeverage,
            $defaultLeverage
        );

        // Risk kontrolü
        $riskAssessment = $this->assessLeverageRisk(
            $finalLeverage,
            $riskProfile,
            $accountBalance,
            $confidence
        );

        Log::info('Leverage calculation completed', [
            'user_id' => $user->id,
            'risk_profile' => $riskProfile['name'] ?? 'unknown',
            'symbol' => $symbol,
            'ai_suggested_leverage' => $aiSuggestedLeverage,
            'ai_confidence' => $confidence,
            'final_leverage' => $finalLeverage,
            'leverage_range' => "{$minLeverage}x - {$effectiveMaxLeverage}x",
            'risk_level' => $riskAssessment['risk_level'],
        ]);

        return [
            'leverage' => $finalLeverage,
            'min_leverage' => $minLeverage,
            'max_leverage' => $effectiveMaxLeverage,
            'ai_suggested' => $aiSuggestedLeverage,
            'confidence_based' => $confidence > 70,
            'risk_level' => $riskAssessment['risk_level'],
            'risk_assessment' => $riskAssessment,
            'calculation_method' => $this->getCalculationMethod($aiSuggestedLeverage, $confidence, $minLeverage, $effectiveMaxLeverage),
            'max_position_size' => $this->calculateMaxPositionSize($finalLeverage, $accountBalance, $riskProfile),
        ];
    }

    /**
     * Maksimum pozisyon büyüklüğünü hesapla
     */
    private function calculateMaxPositionSize(float $leverage, float $accountBalance, array $riskProfile): float
    {
        $equityUsagePct = (float) ($riskProfile['position_sizing']['equity_usage_pct'] ?? 30.0);
        $maxEquityUsage = $accountBalance * ($equityUsagePct / 100);

        return $maxEquityUsage * $leverage;
    }

    /**
     * Final kaldıracı belirle
     */
    private function determineFinalLeverage(
        float $aiSuggested,
        float $confidence,
        float $minLeverage,
        float $maxLeverage,
        float $defaultLeverage
    ): float {
        // AI güveni yüksekse AI önerisini kullan (limitler dahilinde)
        if ($confidence > 70 && $aiSuggested > 0) {
            $clampedLeverage = max($minLeverage, min($maxLeverage, $aiSuggested));

            // AI önerisi limitler dahilindeyse kullan
            if ($clampedLeverage === $aiSuggested) {
                return $aiSuggested;
            }

            // AI önerisi sınırların dışındaysa sınıra yakın değer kullan
            return $clampedLeverage;
        }

        // AI güveni düşükse veya öneri yoksa confidence bazlı hesaplama
        return $this->calculateConfidenceBasedLeverage($confidence, $minLeverage, $maxLeverage, $defaultLeverage);
    }

    /**
     * Confidence bazlı kaldıraç hesaplama
     */
    private function calculateConfidenceBasedLeverage(
        float $confidence,
        float $minLeverage,
        float $maxLeverage,
        float $defaultLeverage
    ): float {
        if ($confidence >= 90) {
            // Çok yüksek güven: Max leverage'ın %90'ı
            return $maxLeverage * 0.9;
        } elseif ($confidence >= 80) {
            // Yüksek güven: Max leverage'ın %70'i
            return $maxLeverage * 0.7;
        } elseif ($confidence >= 70) {
            // Orta-yüksek güven: Max leverage'ın %50'si
            return $maxLeverage * 0.5;
        } elseif ($confidence >= 60) {
            // Orta güven: Default leverage
            return $defaultLeverage;
        } else {
            // Düşük güven: Minimum leverage
            return $minLeverage;
        }
    }

    /**
     * Sembol bazlı maksimum kaldıraç al
     */
    private function getSymbolMaxLeverage(string $symbol): float
    {
        $symbolConfigs = config('trading.symbol_configs', []);

        return (float) ($symbolConfigs[$symbol]['max_leverage'] ?? 50.0);
    }

    /**
     * Kaldıraç riskini değerlendir
     *
     * @return array<string, mixed>
     */
    private function assessLeverageRisk(
        float $leverage,
        array $riskProfile,
        float $accountBalance,
        float $confidence
    ): array {
        $riskLevel = 'low';
        $warnings = [];
        $riskScore = 0;

        // Kaldıraç seviyesi riski
        if ($leverage >= 50) {
            $riskLevel = 'very_high';
            $riskScore += 40;
            $warnings[] = 'Very high leverage (50x+) - extreme liquidation risk';
        } elseif ($leverage >= 20) {
            $riskLevel = 'high';
            $riskScore += 25;
            $warnings[] = 'High leverage (20x+) - significant liquidation risk';
        } elseif ($leverage >= 10) {
            $riskLevel = 'medium';
            $riskScore += 15;
            $warnings[] = 'Medium leverage (10x+) - moderate risk';
        }

        // Confidence vs leverage uyumsuzluğu
        if ($leverage > 10 && $confidence < 70) {
            $riskScore += 20;
            $warnings[] = 'High leverage with low AI confidence - risky combination';
        }

        // Risk profili uyumu
        $profileName = $riskProfile['name'] ?? '';
        if ($profileName === 'conservative' && $leverage > 10) {
            $riskScore += 15;
            $warnings[] = 'High leverage not suitable for conservative profile';
        } elseif ($profileName === 'aggressive' && $leverage < 20) {
            // Bu normal, risk artışı yok
        }

        // Account balance vs leverage
        $positionValue = $accountBalance * ($leverage / 100); // Basit hesaplama
        if ($positionValue > $accountBalance * 0.5) { // %50'den fazlası
            $riskScore += 10;
            $warnings[] = 'Large position size relative to account balance';
        }

        // Final risk level
        if ($riskScore >= 50) {
            $riskLevel = 'critical';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'very_high';
        } elseif ($riskScore >= 20) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 10) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'warnings' => $warnings,
            'liquidation_price_estimate' => $this->estimateLiquidationPrice($leverage),
            'recommended_action' => $this->getRecommendedAction($riskLevel),
        ];
    }

    /**
     * Liquidation fiyat tahmini
     */
    private function estimateLiquidationPrice(float $leverage): string
    {
        // Basit tahmin: Kaldıraç arttıkça liquidation risk artar
        $riskPercentage = (1 / $leverage) * 100;

        return '~'.number_format($riskPercentage, 1).'% price movement against position';
    }

    /**
     * Önerilen aksiyon
     */
    private function getRecommendedAction(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => 'Reduce leverage immediately or skip trade',
            'very_high' => 'Consider reducing leverage significantly',
            'high' => 'Monitor position closely, consider lower leverage',
            'medium' => 'Acceptable risk with proper monitoring',
            'low' => 'Safe leverage level for this trade',
            default => 'Review trade parameters',
        };
    }

    /**
     * Hesaplama yöntemini al
     */
    private function getCalculationMethod(float $aiSuggested, float $confidence, float $min, float $max): string
    {
        if ($confidence > 70 && $aiSuggested > 0 && $aiSuggested >= $min && $aiSuggested <= $max) {
            return 'ai_suggested';
        } elseif ($confidence > 70 && $aiSuggested > 0) {
            return 'ai_suggested_clamped';
        } else {
            return 'confidence_based';
        }
    }

    /**
     * Kullanıcı risk profilini al
     *
     * @return array<string, mixed>
     */
    private function getUserRiskProfile(User $user): array
    {
        $profileName = $user->meta['risk_profile'] ?? 'moderate';
        $profiles = config('risk_profiles.profiles', []);

        return $profiles[$profileName] ?? $profiles['moderate'] ?? [];
    }

    /**
     * Kaldıraç önerisi al (AI için)
     *
     * @return array<string, mixed>
     */
    public function getLeverageRecommendation(User $user, string $symbol): array
    {
        $riskProfile = $this->getUserRiskProfile($user);
        $leverageConfig = $riskProfile['leverage'] ?? ['min' => 3, 'max' => 15, 'default' => 5];
        $symbolMaxLeverage = $this->getSymbolMaxLeverage($symbol);

        $effectiveMax = min((float) $leverageConfig['max'], $symbolMaxLeverage);

        return [
            'min_leverage' => (float) $leverageConfig['min'],
            'max_leverage' => $effectiveMax,
            'default_leverage' => (float) $leverageConfig['default'],
            'recommended_range' => $leverageConfig['min'].'-'.$effectiveMax.'x',
            'risk_profile' => $riskProfile['name'] ?? 'moderate',
            'symbol_max' => $symbolMaxLeverage,
        ];
    }
}
