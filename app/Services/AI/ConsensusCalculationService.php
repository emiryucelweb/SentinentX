<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AiDecision;

final class ConsensusCalculationService
{
    /**
     * Ağırlıklı medyan hesaplama
     */
    public function calculateWeightedMedian(array $decisions, array $weights = []): AiDecision
    {
        if (empty($decisions)) {
            throw new \InvalidArgumentException('Decisions array cannot be empty');
        }

        // Ağırlık yoksa eşit ağırlık kullan
        if (empty($weights)) {
            $weights = array_fill(0, count($decisions), 1.0);
        }

        // Ağırlıkları normalize et
        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn ($w) => $w / $totalWeight, $weights);

        // Confidence'a göre sırala
        $sortedDecisions = collect($decisions)->sortBy('confidence')->values();
        $sortedWeights = collect($normalizedWeights)->sortBy(function ($weight, $index) use ($sortedDecisions) {
            return $sortedDecisions[$index]->confidence;
        })->values();

        // Ağırlıklı medyan bul
        $cumulativeWeight = 0;
        $medianIndex = 0;

        foreach ($sortedWeights as $index => $weight) {
            $cumulativeWeight += $weight;
            if ($cumulativeWeight >= 0.5) {
                $medianIndex = $index;
                break;
            }
        }

        return $sortedDecisions[$medianIndex];
    }

    /**
     * Çoğunluk oylama
     */
    public function calculateMajorityVote(array $decisions): AiDecision
    {
        $actionCounts = [];

        foreach ($decisions as $decision) {
            $action = $decision->action;
            $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
        }

        $majorityAction = array_search(max($actionCounts), $actionCounts);

        // Çoğunluk action'ı olan decision'ı bul
        foreach ($decisions as $decision) {
            if ($decision->action === $majorityAction) {
                return $decision;
            }
        }

        throw new \RuntimeException('Majority decision not found');
    }

    /**
     * Ağırlıklı ortalama hesaplama
     */
    public function calculateWeightedAverage(array $decisions, array $weights = []): AiDecision
    {
        if (empty($decisions)) {
            throw new \InvalidArgumentException('Decisions array cannot be empty');
        }

        // Ağırlık yoksa eşit ağırlık kullan
        if (empty($weights)) {
            $weights = array_fill(0, count($decisions), 1.0);
        }

        // Ağırlıkları normalize et
        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn ($w) => $w / $totalWeight, $weights);

        // Action'ları say
        $actionCounts = [];
        foreach ($decisions as $decision) {
            $action = $decision->action;
            $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
        }

        // En çok oy alan action'ı seç
        $majorityAction = array_search(max($actionCounts), $actionCounts);

        // Ağırlıklı ortalama hesapla
        $avgConfidence = 0;
        $avgStopLoss = 0;
        $avgTakeProfit = 0;
        $avgQtyDeltaFactor = 0;

        foreach ($decisions as $index => $decision) {
            $weight = $normalizedWeights[$index];
            $avgConfidence += $decision->confidence * $weight;
            $avgStopLoss += ($decision->stopLoss ?? 0) * $weight;
            $avgTakeProfit += ($decision->takeProfit ?? 0) * $weight;
            $avgQtyDeltaFactor += ($decision->qtyDeltaFactor ?? 0) * $weight;
        }

        // Reason'ları birleştir
        $reasons = collect($decisions)->pluck('reason')->filter()->unique()->values()->toArray();
        $combinedReason = implode(' | ', $reasons);

        return new AiDecision(
            action: $majorityAction,
            confidence: (int) round($avgConfidence),
            stopLoss: $avgStopLoss > 0 ? $avgStopLoss : null,
            takeProfit: $avgTakeProfit > 0 ? $avgTakeProfit : null,
            qtyDeltaFactor: $avgQtyDeltaFactor,
            reason: $combinedReason,
            raw: ['consensus_method' => 'weighted_average']
        );
    }

    /**
     * Trimmed mean hesaplama (outlier'ları filtrele)
     */
    public function calculateTrimmedMean(array $values, float $trimPercent = 0.1): float
    {
        if (empty($values)) {
            return 0.0;
        }

        // Null değerleri filtrele
        $filteredValues = array_filter($values, fn ($v) => $v !== null);

        if (empty($filteredValues)) {
            return 0.0;
        }

        sort($filteredValues);

        $count = count($filteredValues);
        $trimCount = (int) round($count * $trimPercent);

        $trimmedValues = array_slice($filteredValues, $trimCount, $count - 2 * $trimCount);

        return count($trimmedValues) > 0 ? array_sum($trimmedValues) / count($trimmedValues) : 0.0;
    }

    /**
     * Konsensüs kalitesi skoru
     */
    public function calculateConsensusQuality(array $decisions): float
    {
        if (count($decisions) < 2) {
            return 1.0;
        }

        $confidences = collect($decisions)->pluck('confidence')->toArray();
        $actions = collect($decisions)->pluck('action')->toArray();

        // Confidence tutarlılığı
        $confidenceStd = $this->calculateStandardDeviation($confidences);
        $confidenceMean = array_sum($confidences) / count($confidences);
        $confidenceCV = $confidenceMean > 0 ? $confidenceStd / $confidenceMean : 0;

        // Action tutarlılığı
        $actionCounts = array_count_values($actions);
        $maxActionCount = max($actionCounts);
        $actionConsensus = $maxActionCount / count($decisions);

        // Toplam kalite skoru (0-1 arası, 1 = yüksek kalite)
        $qualityScore = ($actionConsensus * 0.7) + ((1 - $confidenceCV) * 0.3);

        return max(0, min(1, $qualityScore));
    }

    /**
     * Standart sapma hesaplama
     */
    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $values)) / ($count - 1);

        return sqrt($variance);
    }
}
