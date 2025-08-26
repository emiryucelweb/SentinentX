<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class SmartStopLossService
{
    /**
     * AI confidence'a göre SL/TP stratejisini belirle
     *
     * @param array $aiDecision
     * @param array $riskProfile
     * @param float $entryPrice
     * @param string $side
     * @return array<string, mixed>
     */
    public function calculateStopLossTakeProfit(
        array $aiDecision, 
        array $riskProfile, 
        float $entryPrice, 
        string $side
    ): array {
        $confidence = (float) ($aiDecision['confidence'] ?? 0);
        
        if ($confidence > 70) {
            // Yüksek confidence: ChatGPT'nin önerdiği fiyatları kullan
            return $this->useAiSuggestedPrices($aiDecision, $entryPrice, $side);
        } else {
            // Düşük confidence: Risk profili varsayılan oranlarını kullan
            return $this->useRiskProfileDefaults($riskProfile, $entryPrice, $side);
        }
    }

    /**
     * AI'ın önerdiği fiyatları kullan (confidence >70)
     *
     * @param array $aiDecision
     * @param float $entryPrice
     * @param string $side
     * @return array<string, mixed>
     */
    private function useAiSuggestedPrices(array $aiDecision, float $entryPrice, string $side): array
    {
        $aiStopLoss = (float) ($aiDecision['stop_loss'] ?? 0.0);
        $aiTakeProfit = (float) ($aiDecision['take_profit'] ?? 0.0);

        // AI fiyatlarının mantıklı olup olmadığını kontrol et
        if ($this->validateAiPrices($aiStopLoss, $aiTakeProfit, $entryPrice, $side)) {
            Log::info('Using AI suggested SL/TP prices', [
                'confidence' => $aiDecision['confidence'],
                'entry_price' => $entryPrice,
                'ai_stop_loss' => $aiStopLoss,
                'ai_take_profit' => $aiTakeProfit,
                'side' => $side,
            ]);

            $riskDistance = abs($entryPrice - $aiStopLoss);
            $rewardDistance = abs($aiTakeProfit - $entryPrice);
            $riskRewardRatio = $riskDistance > 0 ? $rewardDistance / $riskDistance : 0;

            return [
                'strategy' => 'ai_suggested',
                'stop_loss' => $aiStopLoss,
                'take_profit' => $aiTakeProfit,
                'risk_reward_ratio' => $riskRewardRatio,
                'source' => 'chatgpt_priority',
                'confidence_based' => true,
            ];
        } else {
            Log::warning('AI suggested prices failed validation, falling back to default', [
                'ai_stop_loss' => $aiStopLoss,
                'ai_take_profit' => $aiTakeProfit,
                'entry_price' => $entryPrice,
                'side' => $side,
            ]);

            // Geçersizse fallback olarak varsayılan oranları kullan
            return $this->useDefaultRatios($entryPrice, $side);
        }
    }

    /**
     * Risk profili varsayılan oranlarını kullan (confidence <=70)
     *
     * @param array $riskProfile
     * @param float $entryPrice
     * @param string $side
     * @return array<string, mixed>
     */
    private function useRiskProfileDefaults(array $riskProfile, float $entryPrice, string $side): array
    {
        $stopLossPct = (float) ($riskProfile['risk']['stop_loss_pct'] ?? 3.0);
        $takeProfitPct = (float) ($riskProfile['risk']['take_profit_pct'] ?? 6.0);

        if ($side === 'LONG') {
            $stopLoss = $entryPrice * (1 - $stopLossPct / 100);
            $takeProfit = $entryPrice * (1 + $takeProfitPct / 100);
        } else { // SHORT
            $stopLoss = $entryPrice * (1 + $stopLossPct / 100);
            $takeProfit = $entryPrice * (1 - $takeProfitPct / 100);
        }

        Log::info('Using risk profile default SL/TP ratios', [
            'risk_profile' => $riskProfile['name'] ?? 'unknown',
            'entry_price' => $entryPrice,
            'stop_loss_pct' => $stopLossPct,
            'take_profit_pct' => $takeProfitPct,
            'calculated_stop_loss' => $stopLoss,
            'calculated_take_profit' => $takeProfit,
            'side' => $side,
        ]);

        $riskDistance = abs($entryPrice - $stopLoss);
        $rewardDistance = abs($takeProfit - $entryPrice);
        $riskRewardRatio = $riskDistance > 0 ? $rewardDistance / $riskDistance : 0;

        return [
            'strategy' => 'risk_profile_default',
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'stop_loss_pct' => $stopLossPct,
            'take_profit_pct' => $takeProfitPct,
            'risk_reward_ratio' => $riskRewardRatio,
            'source' => 'risk_profile',
            'confidence_based' => false,
        ];
    }

    /**
     * Varsayılan oranları kullan (fallback)
     *
     * @param float $entryPrice
     * @param string $side
     * @return array<string, mixed>
     */
    private function useDefaultRatios(float $entryPrice, string $side): array
    {
        // Güvenli varsayılan değerler
        $stopLossPct = 4.0; // %4
        $takeProfitPct = 8.0; // %8 (2:1 risk/reward)

        if ($side === 'LONG') {
            $stopLoss = $entryPrice * (1 - $stopLossPct / 100);
            $takeProfit = $entryPrice * (1 + $takeProfitPct / 100);
        } else { // SHORT
            $stopLoss = $entryPrice * (1 + $stopLossPct / 100);
            $takeProfit = $entryPrice * (1 - $takeProfitPct / 100);
        }

        Log::info('Using fallback default SL/TP ratios', [
            'entry_price' => $entryPrice,
            'stop_loss_pct' => $stopLossPct,
            'take_profit_pct' => $takeProfitPct,
            'calculated_stop_loss' => $stopLoss,
            'calculated_take_profit' => $takeProfit,
            'side' => $side,
        ]);

        $riskDistance = abs($entryPrice - $stopLoss);
        $rewardDistance = abs($takeProfit - $entryPrice);
        $riskRewardRatio = $riskDistance > 0 ? $rewardDistance / $riskDistance : 0;

        return [
            'strategy' => 'fallback_default',
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'stop_loss_pct' => $stopLossPct,
            'take_profit_pct' => $takeProfitPct,
            'risk_reward_ratio' => $riskRewardRatio,
            'source' => 'system_fallback',
            'confidence_based' => false,
        ];
    }

    /**
     * AI'ın önerdiği fiyatları doğrula
     *
     * @param float $stopLoss
     * @param float $takeProfit
     * @param float $entryPrice
     * @param string $side
     * @return bool
     */
    private function validateAiPrices(float $stopLoss, float $takeProfit, float $entryPrice, string $side): bool
    {
        // Temel kontroller
        if ($stopLoss <= 0 || $takeProfit <= 0 || $entryPrice <= 0) {
            return false;
        }

        if ($side === 'LONG') {
            // LONG pozisyon: SL < entry < TP
            if ($stopLoss >= $entryPrice || $takeProfit <= $entryPrice) {
                return false;
            }

            // Stop loss çok aşırı olmasın (%20'den fazla)
            $slPct = (($entryPrice - $stopLoss) / $entryPrice) * 100;
            if ($slPct > 20.0) {
                return false;
            }

            // Take profit çok aşırı olmasın (%100'den fazla)
            $tpPct = (($takeProfit - $entryPrice) / $entryPrice) * 100;
            if ($tpPct > 100.0) {
                return false;
            }

        } else { // SHORT
            // SHORT pozisyon: TP < entry < SL
            if ($takeProfit >= $entryPrice || $stopLoss <= $entryPrice) {
                return false;
            }

            // Stop loss çok aşırı olmasın (%20'den fazla)
            $slPct = (($stopLoss - $entryPrice) / $entryPrice) * 100;
            if ($slPct > 20.0) {
                return false;
            }

            // Take profit çok aşırı olmasın (%100'den fazla)
            $tpPct = (($entryPrice - $takeProfit) / $entryPrice) * 100;
            if ($tpPct > 100.0) {
                return false;
            }
        }

        // Risk/reward oranı çok kötü olmasın (minimum 1:1)
        $riskRewardRatio = $this->calculateRiskRewardRatio($stopLoss, $takeProfit, $entryPrice, $side);
        if ($riskRewardRatio < 1.0) {
            return false;
        }

        return true;
    }

    /**
     * Risk/reward oranını hesapla
     *
     * @param float $stopLoss
     * @param float $takeProfit
     * @param float $entryPrice
     * @param string $side
     * @return float
     */
    private function calculateRiskRewardRatio(float $stopLoss, float $takeProfit, float $entryPrice, string $side): float
    {
        if ($side === 'LONG') {
            $risk = $entryPrice - $stopLoss;
            $reward = $takeProfit - $entryPrice;
        } else { // SHORT
            $risk = $stopLoss - $entryPrice;
            $reward = $entryPrice - $takeProfit;
        }

        if ($risk <= 0) {
            return 0.0;
        }

        return $reward / $risk;
    }

    /**
     * ChatGPT öncelikli SL/TP belirleme
     *
     * @param array $aiDecisions 3 AI'ın kararları
     * @param array $riskProfile
     * @param float $entryPrice
     * @param string $side
     * @return array<string, mixed>
     */
    public function determinePriorityBasedStopLoss(
        array $aiDecisions, 
        array $riskProfile, 
        float $entryPrice, 
        string $side
    ): array {
        // ChatGPT (OpenAI) kararını bul
        $chatGptDecision = null;
        foreach ($aiDecisions as $provider => $decision) {
            if (str_contains(strtolower($provider), 'openai') || str_contains(strtolower($provider), 'chatgpt')) {
                $chatGptDecision = $decision;
                break;
            }
        }

        if ($chatGptDecision && ($chatGptDecision['confidence'] ?? 0) > 70) {
            Log::info('Using ChatGPT priority SL/TP (confidence >70)', [
                'provider' => 'ChatGPT',
                'confidence' => $chatGptDecision['confidence'],
            ]);

            return $this->calculateStopLossTakeProfit($chatGptDecision, $riskProfile, $entryPrice, $side);
        }

        // ChatGPT güveni düşükse veya yoksa, en yüksek confidence'lı AI'ı kullan
        $bestDecision = null;
        $bestConfidence = 0;

        foreach ($aiDecisions as $provider => $decision) {
            $confidence = (float) ($decision['confidence'] ?? 0);
            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestDecision = $decision;
            }
        }

        if ($bestDecision && $bestConfidence > 70) {
            Log::info('Using highest confidence AI SL/TP', [
                'confidence' => $bestConfidence,
            ]);

            return $this->calculateStopLossTakeProfit($bestDecision, $riskProfile, $entryPrice, $side);
        }

        // Tüm AI'lar düşük güvenliyse risk profili varsayılanlarını kullan
        Log::info('All AI confidence low, using risk profile defaults', [
            'best_confidence' => $bestConfidence,
        ]);

        return $this->useRiskProfileDefaults($riskProfile, $entryPrice, $side);
    }
}
