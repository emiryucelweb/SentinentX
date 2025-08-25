<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AiDecision;

final class AiOutputSchemaService
{
    /**
     * AI çıkışını standart şemaya dönüştür
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function standardizeOutput(AiDecision $decision, array $context = []): array
    {
        return [
            'action' => $this->normalizeAction($decision->action),
            'confidence' => $this->normalizeConfidence($decision->confidence),
            'stop_loss' => $this->normalizePrice($decision->stopLoss),
            'take_profit' => $this->normalizePrice($decision->takeProfit),
            'quantity_factor' => $this->normalizeQuantityFactor($decision->qtyDeltaFactor),
            'reason' => $this->normalizeReason($decision->reason),
            'risk_score' => $this->calculateRiskScore($decision, $context),
            'execution_priority' => $this->calculateExecutionPriority($decision, $context),
            'metadata' => [
                'raw_decision' => $decision->raw,
                'context' => $context,
                'timestamp' => time(),
                'schema_version' => 'v1.0',
            ],
        ];
    }

    /**
     * Action'ı normalize et
     */
    private function normalizeAction(string $action): string
    {
        $actionMap = [
            'BUY' => 'LONG',
            'SELL' => 'SHORT',
            'LONG' => 'LONG',
            'SHORT' => 'SHORT',
            'HOLD' => 'HOLD',
            'CLOSE' => 'CLOSE',
            'NO_TRADE' => 'NO_TRADE',
        ];

        return $actionMap[strtoupper($action)] ?? 'NO_TRADE';
    }

    /**
     * Confidence'ı normalize et (0-100 arası)
     */
    private function normalizeConfidence(int $confidence): int
    {
        return max(0, min(100, $confidence));
    }

    /**
     * Fiyatı normalize et
     */
    private function normalizePrice(?float $price): ?float
    {
        if ($price === null || $price <= 0) {
            return null;
        }

        // 8 ondalık basamağa yuvarla
        return round($price, 8);
    }

    /**
     * Quantity factor'ı normalize et (-1 ile 1 arası)
     */
    private function normalizeQuantityFactor(?float $factor): float
    {
        if ($factor === null) {
            return 1.0; // Default factor
        }

        return max(-1, min(1, $factor));
    }

    /**
     * Reason'ı normalize et
     */
    private function normalizeReason(string $reason): string
    {
        // Reason'ı temizle ve kısalt
        $cleanReason = trim($reason);

        if (strlen($cleanReason) > 200) {
            $cleanReason = substr($cleanReason, 0, 197).'...';
        }

        return $cleanReason ?: 'No reason provided';
    }

    /**
     * Risk skoru hesapla (0-10 arası, 10 = yüksek risk)
     * @param array<string, mixed> $context
     */
    private function calculateRiskScore(AiDecision $decision, array $context): int
    {
        $score = 0;

        // Confidence bazlı risk
        if ($decision->confidence < 50) {
            $score += 3;
        } elseif ($decision->confidence < 70) {
            $score += 2;
        } elseif ($decision->confidence < 85) {
            $score += 1;
        }

        // Leverage bazlı risk
        $leverage = $decision->raw['leverage'] ?? $decision->raw['lev'] ?? 1;
        if ($leverage > 10) {
            $score += 3;
        } elseif ($leverage > 5) {
            $score += 2;
        } elseif ($leverage > 2) {
            $score += 1;
        }

        // Volatilite bazlı risk
        $volatility = $context['technical']['volatility'] ?? 0;
        if ($volatility > 0.05) {
            $score += 2;
        } elseif ($volatility > 0.03) {
            $score += 1;
        }

        // Funding rate bazlı risk
        $fundingRate = $context['market']['funding_rate'] ?? 0;
        if (abs($fundingRate) > 0.001) {
            $score += 1;
        }

        return min(10, $score);
    }

    /**
     * Execution priority hesapla (1-5 arası, 1 = yüksek öncelik)
     * @param array<string, mixed> $context
     */
    private function calculateExecutionPriority(AiDecision $decision, array $context): int
    {
        $priority = 3; // Varsayılan orta öncelik

        // Confidence bazlı öncelik
        if ($decision->confidence >= 90) {
            $priority -= 2;
        } elseif ($decision->confidence >= 80) {
            $priority -= 1;
        } elseif ($decision->confidence < 60) {
            $priority += 1;
        }

        // Risk skoru bazlı öncelik
        $riskScore = $this->calculateRiskScore($decision, $context);
        if ($riskScore >= 8) {
            $priority += 1;
        } elseif ($riskScore <= 3) {
            $priority -= 1;
        }

        // Market koşulları bazlı öncelik
        $rsi = $context['technical']['rsi'] ?? 50;
        if ($rsi < 20 || $rsi > 80) {
            $priority -= 1; // Extreme koşullarda daha hızlı
        }

        return max(1, min(5, $priority));
    }

    /**
     * Çoklu AI çıkışını birleştir
     * @param array<AiDecision> $decisions
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function mergeMultipleOutputs(array $decisions, array $context = []): array
    {
        if (empty($decisions)) {
            return $this->standardizeOutput(new AiDecision(
                action: 'NO_TRADE',
                confidence: 0,
                stopLoss: null,
                takeProfit: null,
                qtyDeltaFactor: 0.0,
                reason: 'No decisions available',
                raw: []
            ), $context);
        }

        // En yüksek confidence'lı decision'ı seç
        $bestDecision = collect($decisions)->sortByDesc('confidence')->first();
        
        // Null check for safety
        if ($bestDecision === null) {
            return $this->standardizeOutput(new AiDecision(
                action: 'NO_TRADE',
                confidence: 0,
                stopLoss: null,
                takeProfit: null,
                qtyDeltaFactor: 0.0,
                reason: 'Best decision not found',
                raw: []
            ), $context);
        }

        // Ortalama değerleri hesapla
        $avgStopLoss = collect($decisions)->pluck('stopLoss')->filter()->avg();
        $avgTakeProfit = collect($decisions)->pluck('takeProfit')->filter()->avg();
        $avgQtyDeltaFactor = collect($decisions)->pluck('qtyDeltaFactor')->filter()->avg();

        // Reason'ları birleştir
        $reasons = collect($decisions)->pluck('reason')->filter()->unique()->values()->toArray();
        $combinedReason = implode(' | ', $reasons);

        $mergedDecision = new AiDecision(
            action: $bestDecision->action,
            confidence: $bestDecision->confidence,
            stopLoss: $avgStopLoss,
            takeProfit: $avgTakeProfit,
            qtyDeltaFactor: $avgQtyDeltaFactor,
            reason: $combinedReason,
            raw: array_merge($bestDecision->raw ?? [], ['merged_from' => count($decisions)])
        );

        return $this->standardizeOutput($mergedDecision, $context);
    }
}
