<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Services\Market\CoinGeckoService;
use App\Services\Market\BybitMarketData;
use App\Services\AI\ConsensusService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MultiCoinAnalysisService
{
    private const SUPPORTED_COINS = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];
    private const CACHE_TTL = 180; // 3 dakika cache

    public function __construct(
        private readonly CoinGeckoService $coinGeckoService,
        private readonly BybitMarketData $bybitMarketData,
        private readonly ConsensusService $consensusService,
    ) {}

    /**
     * 4 coin için paralel analiz yap ve en güvenilir olanı seç
     *
     * @param User $user
     * @param string|null $userReason
     * @return array<string, mixed>
     */
    public function analyzeAllCoins(User $user, ?string $userReason = null): array
    {
        $cacheKey = "multi_coin_analysis_{$user->id}_" . md5($userReason ?? '');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $userReason) {
            Log::info('Starting multi-coin analysis', [
                'user_id' => $user->id,
                'user_reason' => $userReason,
                'coins' => self::SUPPORTED_COINS,
            ]);

            // 1. Tüm coinlerin market verilerini topla
            $marketData = $this->collectMarketData();

            // 2. Her coin için AI analizi yap
            $analysisResults = [];
            foreach (self::SUPPORTED_COINS as $symbol) {
                $analysisResults[$symbol] = $this->analyzeSingleCoin(
                    $symbol, 
                    $marketData[$symbol] ?? [], 
                    $user, 
                    $userReason
                );
            }

            // 3. En güvenilir coini seç
            $bestCoin = $this->selectMostReliableCoin($analysisResults);

            // 4. Seçim gerekçesini oluştur
            $selectionReason = $this->buildSelectionReason($analysisResults, $bestCoin);

            $result = [
                'success' => true,
                'selected_coin' => $bestCoin,
                'selection_reason' => $selectionReason,
                'all_analyses' => $analysisResults,
                'market_overview' => $this->buildMarketOverview($marketData),
                'timestamp' => now()->toISOString(),
                'user_risk_profile' => $this->getUserRiskProfile($user),
            ];

            Log::info('Multi-coin analysis completed', [
                'user_id' => $user->id,
                'selected_coin' => $bestCoin,
                'total_coins_analyzed' => count($analysisResults),
            ]);

            return $result;
        });
    }

    /**
     * Tüm coinlerin market verilerini topla
     *
     * @return array<string, array>
     */
    private function collectMarketData(): array
    {
        $marketData = [];

        // CoinGecko verilerini al
        $coinGeckoData = $this->coinGeckoService->getMultiCoinData();

        // Her coin için Bybit + CoinGecko verilerini birleştir
        foreach (self::SUPPORTED_COINS as $symbol) {
            try {
                // Bybit market data
                $bybitTicker = $this->bybitMarketData->getTicker($symbol);
                $bybitKlines = $this->bybitMarketData->getKlines($symbol, '1', 50);
                $bybitOrderbook = $this->bybitMarketData->getOrderbook($symbol, 25);

                // CoinGecko data
                $coinGeckoInfo = $coinGeckoData[$symbol] ?? [];

                $marketData[$symbol] = [
                    'symbol' => $symbol,
                    'bybit' => [
                        'ticker' => $bybitTicker,
                        'klines' => $bybitKlines,
                        'orderbook' => $bybitOrderbook,
                    ],
                    'coingecko' => $coinGeckoInfo,
                    'reliability_score' => $coinGeckoInfo['reliability_score'] ?? 0.0,
                    'sentiment_score' => $coinGeckoInfo['sentiment'] ?? 50.0,
                ];

            } catch (\Throwable $e) {
                Log::error('Error collecting market data for coin', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);

                $marketData[$symbol] = [
                    'symbol' => $symbol,
                    'error' => 'Data collection failed',
                    'reliability_score' => 0.0,
                    'sentiment_score' => 25.0,
                ];
            }
        }

        return $marketData;
    }

    /**
     * Tek coin için AI analizi yap
     *
     * @param string $symbol
     * @param array $marketData
     * @param User $user
     * @param string|null $userReason
     * @return array<string, mixed>
     */
    private function analyzeSingleCoin(string $symbol, array $marketData, User $user, ?string $userReason): array
    {
        try {
            // Risk profilini al
            $riskProfile = $this->getUserRiskProfile($user);

            // AI için snapshot hazırla
            $snapshot = [
                'symbol' => $symbol,
                'price' => $marketData['bybit']['ticker']['data']['last_price'] ?? 0.0,
                'market_data' => $marketData,
                'coingecko' => $marketData['coingecko'] ?? [],
                'risk_profile' => $riskProfile,
                'balance' => $this->getUserBalance($user),
                'open_positions' => $this->getOpenPositions($user),
            ];

            if ($userReason) {
                $snapshot['user_intent'] = ['reason' => $userReason];
            }

            // AI consensus çalıştır
            $consensusResult = $this->consensusService->decide($snapshot);

            return [
                'symbol' => $symbol,
                'ai_decision' => $consensusResult,
                'market_data' => $marketData,
                'reliability_score' => $marketData['reliability_score'] ?? 0.0,
                'sentiment_score' => $marketData['sentiment_score'] ?? 50.0,
                'combined_score' => $this->calculateCombinedScore($consensusResult, $marketData),
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Throwable $e) {
            Log::error('Error analyzing single coin', [
                'symbol' => $symbol,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'symbol' => $symbol,
                'error' => 'Analysis failed',
                'ai_decision' => ['action' => 'NONE', 'confidence' => 0],
                'reliability_score' => 0.0,
                'sentiment_score' => 25.0,
                'combined_score' => 0.0,
            ];
        }
    }

    /**
     * En güvenilir coini seç
     *
     * @param array $analysisResults
     * @return string|null
     */
    private function selectMostReliableCoin(array $analysisResults): ?string
    {
        $validCandidates = [];

        foreach ($analysisResults as $symbol => $analysis) {
            $aiDecision = $analysis['ai_decision'] ?? [];
            
            // Sadece AL/SAT kararları değerlendir
            if (($aiDecision['action'] ?? 'NONE') === 'NONE') {
                continue;
            }

            $combinedScore = $analysis['combined_score'] ?? 0.0;
            $confidence = $aiDecision['confidence'] ?? 0;

            $validCandidates[$symbol] = [
                'combined_score' => $combinedScore,
                'confidence' => $confidence,
                'reliability' => $analysis['reliability_score'] ?? 0.0,
                'sentiment' => $analysis['sentiment_score'] ?? 50.0,
                'action' => $aiDecision['action'] ?? 'NONE',
            ];
        }

        if (empty($validCandidates)) {
            Log::info('No valid trading candidates found');
            return null;
        }

        // Combined score'a göre sırala (en yüksek önce)
        uasort($validCandidates, function ($a, $b) {
            return $b['combined_score'] <=> $a['combined_score'];
        });

        $selectedSymbol = array_key_first($validCandidates);
        
        Log::info('Most reliable coin selected', [
            'selected' => $selectedSymbol,
            'score' => $validCandidates[$selectedSymbol]['combined_score'],
            'confidence' => $validCandidates[$selectedSymbol]['confidence'],
            'total_candidates' => count($validCandidates),
        ]);

        return $selectedSymbol;
    }

    /**
     * Combined score hesapla (AI confidence + market reliability + sentiment)
     *
     * @param array $aiDecision
     * @param array $marketData
     * @return float
     */
    private function calculateCombinedScore(array $aiDecision, array $marketData): float
    {
        $aiConfidence = (float) ($aiDecision['confidence'] ?? 0);
        $reliability = (float) ($marketData['reliability_score'] ?? 0.0);
        $sentiment = (float) ($marketData['sentiment_score'] ?? 50.0);

        // Ağırlıklı ortalama
        $combined = (
            $aiConfidence * 0.5 +        // AI güveni %50 ağırlık
            $reliability * 0.3 +         // Market güvenilirliği %30
            ($sentiment - 50) * 0.2      // Sentiment (%50 nötr, sapma +/- puan)
        );

        return max(0.0, min(100.0, $combined));
    }

    /**
     * Seçim gerekçesini oluştur
     *
     * @param array $analysisResults
     * @param string|null $bestCoin
     * @return string
     */
    private function buildSelectionReason(array $analysisResults, ?string $bestCoin): string
    {
        if (!$bestCoin) {
            return 'Hiçbir coin için uygun trading sinyali bulunamadı. Tüm AI analizleri BEKLE kararı verdi.';
        }

        $analysis = $analysisResults[$bestCoin];
        $aiDecision = $analysis['ai_decision'] ?? [];
        $combined = $analysis['combined_score'] ?? 0.0;
        $confidence = $aiDecision['confidence'] ?? 0;
        $action = $aiDecision['action'] ?? 'NONE';

        $coinName = config("trading.symbol_configs.{$bestCoin}.display_name", $bestCoin);

        $reason = "{$coinName} seçildi. ";
        $reason .= "AI Güven: %{$confidence}, Combined Score: " . number_format($combined, 1) . ". ";
        $reason .= "Karar: {$action}. ";

        if (isset($aiDecision['reason'])) {
            $reason .= "AI Gerekçesi: {$aiDecision['reason']}";
        }

        return $reason;
    }

    /**
     * Market genel durumu özeti
     *
     * @param array $marketData
     * @return array<string, mixed>
     */
    private function buildMarketOverview(array $marketData): array
    {
        $overview = [
            'total_coins' => count($marketData),
            'avg_reliability' => 0.0,
            'avg_sentiment' => 0.0,
            'market_trend' => 'neutral',
        ];

        $reliabilitySum = 0.0;
        $sentimentSum = 0.0;
        $positiveCount = 0;
        $validCount = 0;

        foreach ($marketData as $data) {
            if (isset($data['reliability_score'], $data['sentiment_score'])) {
                $reliabilitySum += $data['reliability_score'];
                $sentimentSum += $data['sentiment_score'];
                
                if ($data['sentiment_score'] > 55) {
                    $positiveCount++;
                }
                
                $validCount++;
            }
        }

        if ($validCount > 0) {
            $overview['avg_reliability'] = $reliabilitySum / $validCount;
            $overview['avg_sentiment'] = $sentimentSum / $validCount;
            
            if ($positiveCount >= ($validCount * 0.75)) {
                $overview['market_trend'] = 'bullish';
            } elseif ($positiveCount <= ($validCount * 0.25)) {
                $overview['market_trend'] = 'bearish';
            }
        }

        return $overview;
    }

    /**
     * Kullanıcı risk profilini al
     *
     * @param User $user
     * @return array<string, mixed>
     */
    private function getUserRiskProfile(User $user): array
    {
        $profileName = $user->meta['risk_profile'] ?? 'moderate';
        $profiles = config('risk_profiles.profiles', []);
        
        return $profiles[$profileName] ?? $profiles['moderate'] ?? [];
    }

    /**
     * Kullanıcı bakiyesini al (mock)
     *
     * @param User $user
     * @return array<string, mixed>
     */
    private function getUserBalance(User $user): array
    {
        // Bu gerçek implementasyonda Bybit'ten gelecek
        return [
            'total_equity' => 10000.0,
            'available_balance' => 8500.0,
            'used_margin' => 1500.0,
        ];
    }

    /**
     * Açık pozisyonları al (mock)
     *
     * @param User $user
     * @return array<string, mixed>
     */
    private function getOpenPositions(User $user): array
    {
        // Bu gerçek implementasyonda database'den gelecek
        return [];
    }
}
