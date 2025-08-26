<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\User;
use App\Models\Trade;
use App\Services\AI\ConsensusService;
use App\Services\AI\SmartStopLossService;
use App\Services\Market\BybitMarketData;
use App\Services\Market\CoinGeckoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PositionMonitoringService
{
    public function __construct(
        private readonly ConsensusService $consensusService,
        private readonly SmartStopLossService $smartStopLossService,
        private readonly BybitMarketData $bybitMarketData,
        private readonly CoinGeckoService $coinGeckoService,
    ) {}

    /**
     * Kullanıcının risk profiline göre pozisyon izleme
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function monitorUserPositions(User $user): array
    {
        $riskProfile = $this->getUserRiskProfile($user);
        $checkInterval = $this->getCheckInterval($riskProfile);

        Log::info('Starting position monitoring for user', [
            'user_id' => $user->id,
            'risk_profile' => $riskProfile['name'] ?? 'unknown',
            'check_interval_minutes' => $checkInterval,
        ]);

        // Açık pozisyonları al
        $openPositions = Trade::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->get();

        if ($openPositions->isEmpty()) {
            return [
                'status' => 'no_positions',
                'message' => 'Açık pozisyon bulunamadı',
                'check_interval_minutes' => $checkInterval,
            ];
        }

        $monitoringResults = [];
        $totalActions = 0;

        foreach ($openPositions as $position) {
            $result = $this->monitorSinglePosition($position, $riskProfile);
            $monitoringResults[] = $result;
            
            if (!empty($result['actions'])) {
                $totalActions += count($result['actions']);
            }
        }

        return [
            'status' => 'monitoring_completed',
            'total_positions' => $openPositions->count(),
            'total_actions' => $totalActions,
            'results' => $monitoringResults,
            'check_interval_minutes' => $checkInterval,
            'next_check_at' => now()->addMinutes($checkInterval)->toISOString(),
        ];
    }

    /**
     * Tek pozisyon izleme
     *
     * @param Trade $position
     * @param array $riskProfile
     * @return array<string, mixed>
     */
    private function monitorSinglePosition(Trade $position, array $riskProfile): array
    {
        try {
            // Mevcut market verilerini al
            $currentPrice = $this->getCurrentPrice($position->symbol);
            
            if (!$currentPrice) {
                return [
                    'position_id' => $position->id,
                    'symbol' => $position->symbol,
                    'error' => 'Could not fetch current price',
                    'actions' => [],
                ];
            }

            // PnL hesapla
            $pnlData = $this->calculatePnL($position, $currentPrice);

            // Market context hazırla
            $marketContext = $this->buildMarketContext($position->symbol, $currentPrice);

            // AI consensus için snapshot hazırla
            $snapshot = [
                'symbol' => $position->symbol,
                'price' => $currentPrice,
                'position' => [
                    'entry_price' => $position->entry_price,
                    'side' => $position->side,
                    'qty' => $position->qty,
                    'current_pnl' => $pnlData['pnl'],
                    'pnl_percentage' => $pnlData['pnl_percentage'],
                    'opened_at' => $position->created_at->toISOString(),
                ],
                'market_data' => $marketContext,
                'risk_profile' => $riskProfile,
            ];

            // AI'lardan karar al
            $aiDecision = $this->consensusService->decidePositionManagement($snapshot);

            // Aksiyonları belirle
            $actions = $this->determineActions($position, $aiDecision, $riskProfile, $pnlData);

            Log::info('Position monitoring completed', [
                'position_id' => $position->id,
                'symbol' => $position->symbol,
                'current_price' => $currentPrice,
                'pnl' => $pnlData['pnl'],
                'ai_action' => $aiDecision['action'] ?? 'NONE',
                'actions_count' => count($actions),
            ]);

            return [
                'position_id' => $position->id,
                'symbol' => $position->symbol,
                'current_price' => $currentPrice,
                'pnl_data' => $pnlData,
                'ai_decision' => $aiDecision,
                'actions' => $actions,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Throwable $e) {
            Log::error('Error monitoring position', [
                'position_id' => $position->id,
                'symbol' => $position->symbol,
                'error' => $e->getMessage(),
            ]);

            return [
                'position_id' => $position->id,
                'symbol' => $position->symbol,
                'error' => $e->getMessage(),
                'actions' => [],
            ];
        }
    }

    /**
     * Risk profiline göre kontrol aralığını belirle
     *
     * @param array $riskProfile
     * @return float
     */
    private function getCheckInterval(array $riskProfile): float
    {
        return (float) ($riskProfile['timing']['position_check_minutes'] ?? 3.0);
    }

    /**
     * Mevcut fiyatı al
     *
     * @param string $symbol
     * @return float|null
     */
    private function getCurrentPrice(string $symbol): ?float
    {
        $cacheKey = "current_price_{$symbol}";
        
        return Cache::remember($cacheKey, 30, function () use ($symbol) {
            $ticker = $this->bybitMarketData->getTicker($symbol);
            
            if ($ticker['success'] ?? false) {
                return (float) ($ticker['data']['last_price'] ?? 0.0);
            }
            
            return null;
        });
    }

    /**
     * PnL hesapla
     *
     * @param Trade $position
     * @param float $currentPrice
     * @return array<string, mixed>
     */
    private function calculatePnL(Trade $position, float $currentPrice): array
    {
        $entryPrice = (float) $position->entry_price;
        $qty = (float) $position->qty;
        
        if ($position->side === 'LONG') {
            $pnl = ($currentPrice - $entryPrice) * $qty;
            $pnlPercentage = (($currentPrice - $entryPrice) / $entryPrice) * 100;
        } else { // SHORT
            $pnl = ($entryPrice - $currentPrice) * $qty;
            $pnlPercentage = (($entryPrice - $currentPrice) / $entryPrice) * 100;
        }

        return [
            'pnl' => $pnl,
            'pnl_percentage' => $pnlPercentage,
            'entry_price' => $entryPrice,
            'current_price' => $currentPrice,
            'qty' => $qty,
            'side' => $position->side,
        ];
    }

    /**
     * Market context hazırla
     *
     * @param string $symbol
     * @param float $currentPrice
     * @return array<string, mixed>
     */
    private function buildMarketContext(string $symbol, float $currentPrice): array
    {
        try {
            // Bybit market data
            $klines = $this->bybitMarketData->getKlines($symbol, '1', 20);
            $orderbook = $this->bybitMarketData->getOrderbook($symbol, 10);
            
            // CoinGecko data
            $coinGeckoData = $this->coinGeckoService->getCoinData($symbol);

            return [
                'current_price' => $currentPrice,
                'klines' => $klines['data'] ?? [],
                'orderbook' => $orderbook['data'] ?? [],
                'coingecko' => $coinGeckoData,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Throwable $e) {
            Log::error('Error building market context', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return [
                'current_price' => $currentPrice,
                'error' => 'Market data fetch failed',
            ];
        }
    }

    /**
     * AI kararına göre aksiyonları belirle
     *
     * @param Trade $position
     * @param array $aiDecision
     * @param array $riskProfile
     * @param array $pnlData
     * @return array<string, mixed>
     */
    private function determineActions(Trade $position, array $aiDecision, array $riskProfile, array $pnlData): array
    {
        $actions = [];
        $aiAction = $aiDecision['action'] ?? 'HOLD';
        $confidence = (float) ($aiDecision['confidence'] ?? 0);

        // AI karar aksiyonları
        switch ($aiAction) {
            case 'CLOSE':
                $actions[] = [
                    'type' => 'close_position',
                    'reason' => 'AI consensus: CLOSE',
                    'confidence' => $confidence,
                    'priority' => 'high',
                ];
                break;

            case 'PARTIAL_CLOSE':
                $closePct = (float) ($aiDecision['close_percentage'] ?? 50.0);
                $actions[] = [
                    'type' => 'partial_close',
                    'percentage' => $closePct,
                    'reason' => "AI consensus: Close {$closePct}%",
                    'confidence' => $confidence,
                    'priority' => 'medium',
                ];
                break;

            case 'ADJUST_SL':
                if (isset($aiDecision['new_stop_loss'])) {
                    $actions[] = [
                        'type' => 'adjust_stop_loss',
                        'new_stop_loss' => (float) $aiDecision['new_stop_loss'],
                        'reason' => 'AI suggested SL adjustment',
                        'confidence' => $confidence,
                        'priority' => 'medium',
                    ];
                }
                break;

            case 'ADJUST_TP':
                if (isset($aiDecision['new_take_profit'])) {
                    $actions[] = [
                        'type' => 'adjust_take_profit',
                        'new_take_profit' => (float) $aiDecision['new_take_profit'],
                        'reason' => 'AI suggested TP adjustment',
                        'confidence' => $confidence,
                        'priority' => 'low',
                    ];
                }
                break;
        }

        // Risk bazlı acil aksiyonlar
        $emergencyActions = $this->checkEmergencyConditions($position, $pnlData, $riskProfile);
        $actions = array_merge($actions, $emergencyActions);

        return $actions;
    }

    /**
     * Acil durum koşullarını kontrol et
     *
     * @param Trade $position
     * @param array $pnlData
     * @param array $riskProfile
     * @return array<string, mixed>
     */
    private function checkEmergencyConditions(Trade $position, array $pnlData, array $riskProfile): array
    {
        $actions = [];
        $pnlPercentage = $pnlData['pnl_percentage'];

        // Günlük kar hedefine ulaşıldı mı?
        $dailyTarget = (float) ($riskProfile['risk']['daily_profit_target_pct'] ?? 20.0);
        if ($pnlPercentage >= $dailyTarget) {
            $actions[] = [
                'type' => 'take_profit_target_reached',
                'reason' => "Daily profit target ({$dailyTarget}%) reached",
                'priority' => 'high',
                'auto_execute' => true,
            ];
        }

        // Maximum kayıp limiti
        $maxLossPct = (float) ($riskProfile['risk']['stop_loss_pct'] ?? 5.0) * 2; // 2x güvenlik marjı
        if ($pnlPercentage <= -$maxLossPct) {
            $actions[] = [
                'type' => 'emergency_stop_loss',
                'reason' => "Emergency stop loss triggered ({$maxLossPct}%)",
                'priority' => 'critical',
                'auto_execute' => true,
            ];
        }

        // Pozisyon yaşı kontrolü (24 saatten eski)
        $positionAge = now()->diffInHours($position->created_at);
        if ($positionAge > 24) {
            $actions[] = [
                'type' => 'position_age_warning',
                'reason' => "Position open for {$positionAge} hours",
                'priority' => 'low',
                'auto_execute' => false,
            ];
        }

        return $actions;
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
     * Zamanlı pozisyon izleme başlat
     *
     * @param User $user
     * @return void
     */
    public function schedulePositionMonitoring(User $user): void
    {
        $riskProfile = $this->getUserRiskProfile($user);
        $intervalMinutes = $this->getCheckInterval($riskProfile);

        // Cache'e bir sonraki kontrol zamanını kaydet
        $nextCheckKey = "next_position_check_{$user->id}";
        $nextCheckTime = now()->addMinutes($intervalMinutes);
        
        Cache::put($nextCheckKey, $nextCheckTime->toISOString(), $intervalMinutes * 60 + 300); // +5dk buffer

        Log::info('Position monitoring scheduled', [
            'user_id' => $user->id,
            'interval_minutes' => $intervalMinutes,
            'next_check_at' => $nextCheckTime->toISOString(),
        ]);
    }

    /**
     * Pozisyon izleme zamanı geldi mi?
     *
     * @param User $user
     * @return bool
     */
    public function isMonitoringDue(User $user): bool
    {
        $nextCheckKey = "next_position_check_{$user->id}";
        $nextCheckTime = Cache::get($nextCheckKey);

        if (!$nextCheckTime) {
            return true; // İlk kontrol
        }

        return now()->greaterThanOrEqualTo($nextCheckTime);
    }
}
