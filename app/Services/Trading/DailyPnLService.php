<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\User;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyPnLService
{
    /**
     * Kullanıcının günlük PnL'ini hesapla (00:00 - 00:00)
     *
     * @param User $user
     * @param Carbon|null $date
     * @return array<string, mixed>
     */
    public function calculateDailyPnL(User $user, ?Carbon $date = null): array
    {
        $targetDate = $date ?? now();
        $dayStart = $targetDate->copy()->startOfDay();
        $dayEnd = $targetDate->copy()->endOfDay();

        $cacheKey = "daily_pnl_{$user->id}_{$dayStart->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($user, $dayStart, $dayEnd) {
            try {
                // Gün içinde açılan ve kapanan işlemler
                $completedTrades = $this->getCompletedTrades($user, $dayStart, $dayEnd);
                
                // Gün içinde açılan ama hala açık olan işlemler
                $openTrades = $this->getOpenTrades($user, $dayStart, $dayEnd);
                
                // Önceki günlerden açık kalan işlemler
                $carryOverTrades = $this->getCarryOverTrades($user, $dayStart);

                // PnL hesaplamaları
                $completedPnL = $this->calculateCompletedPnL($completedTrades);
                $unrealizedPnL = $this->calculateUnrealizedPnL($openTrades, $carryOverTrades);
                
                $totalDailyPnL = $completedPnL['total'] + $unrealizedPnL['total'];
                $totalFees = $completedPnL['fees'] + $unrealizedPnL['fees'];
                $netPnL = $totalDailyPnL - $totalFees;

                // Risk profili hedefi
                $riskProfile = $this->getUserRiskProfile($user);
                $dailyTarget = (float) ($riskProfile['risk']['daily_profit_target_pct'] ?? 20.0);

                // Hedef karşılaştırması
                $targetReached = $netPnL >= $dailyTarget;
                $targetProgress = $dailyTarget > 0 ? ($netPnL / $dailyTarget) * 100 : 0;

                $result = [
                    'date' => $dayStart->format('Y-m-d'),
                    'user_id' => $user->id,
                    'risk_profile' => $riskProfile['name'] ?? 'moderate',
                    'daily_target_pct' => $dailyTarget,
                    'pnl_breakdown' => [
                        'completed_trades' => $completedPnL,
                        'unrealized_pnl' => $unrealizedPnL,
                        'total_pnl' => $totalDailyPnL,
                        'total_fees' => $totalFees,
                        'net_pnl' => $netPnL,
                    ],
                    'target_analysis' => [
                        'target_reached' => $targetReached,
                        'target_progress_pct' => $targetProgress,
                        'remaining_to_target' => max(0, $dailyTarget - $netPnL),
                    ],
                    'trade_counts' => [
                        'completed' => count($completedTrades),
                        'open' => count($openTrades),
                        'carry_over' => count($carryOverTrades),
                        'total' => count($completedTrades) + count($openTrades) + count($carryOverTrades),
                    ],
                    'calculated_at' => now()->toISOString(),
                ];

                Log::info('Daily PnL calculated', [
                    'user_id' => $user->id,
                    'date' => $dayStart->format('Y-m-d'),
                    'net_pnl' => $netPnL,
                    'target_progress' => $targetProgress,
                    'target_reached' => $targetReached,
                ]);

                return $result;

            } catch (\Throwable $e) {
                Log::error('Error calculating daily PnL', [
                    'user_id' => $user->id,
                    'date' => $dayStart->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);

                return [
                    'error' => 'PnL calculation failed',
                    'date' => $dayStart->format('Y-m-d'),
                    'user_id' => $user->id,
                ];
            }
        });
    }

    /**
     * AI için günlük PnL context hazırla
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function getDailyPnLForAI(User $user): array
    {
        $dailyPnL = $this->calculateDailyPnL($user);
        
        if (isset($dailyPnL['error'])) {
            return [
                'daily_pnl' => 0.0,
                'target_progress' => 0.0,
                'target_reached' => false,
                'trade_count' => 0,
                'risk_status' => 'unknown',
            ];
        }

        $netPnL = $dailyPnL['pnl_breakdown']['net_pnl'] ?? 0.0;
        $targetProgress = $dailyPnL['target_analysis']['target_progress_pct'] ?? 0.0;
        $targetReached = $dailyPnL['target_analysis']['target_reached'] ?? false;

        // Risk durumu
        $riskStatus = $this->assessDailyRiskStatus($dailyPnL);

        return [
            'daily_pnl' => $netPnL,
            'daily_pnl_percentage' => $targetProgress,
            'target_progress' => $targetProgress,
            'target_reached' => $targetReached,
            'trade_count' => $dailyPnL['trade_counts']['total'] ?? 0,
            'risk_status' => $riskStatus,
            'should_continue_trading' => $this->shouldContinueTrading($dailyPnL),
            'recommendation' => $this->getAIRecommendation($dailyPnL),
        ];
    }

    /**
     * Gün içinde tamamlanan işlemleri al
     */
    private function getCompletedTrades(User $user, Carbon $dayStart, Carbon $dayEnd): array
    {
        return Trade::where('user_id', $user->id)
            ->where('status', 'CLOSED')
            ->whereBetween('closed_at', [$dayStart, $dayEnd])
            ->get()
            ->toArray();
    }

    /**
     * Gün içinde açılan ama hala açık olan işlemleri al
     */
    private function getOpenTrades(User $user, Carbon $dayStart, Carbon $dayEnd): array
    {
        return Trade::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->get()
            ->toArray();
    }

    /**
     * Önceki günlerden açık kalan işlemleri al
     */
    private function getCarryOverTrades(User $user, Carbon $dayStart): array
    {
        return Trade::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->where('created_at', '<', $dayStart)
            ->get()
            ->toArray();
    }

    /**
     * Tamamlanan işlemlerin PnL'ini hesapla
     */
    private function calculateCompletedPnL(array $trades): array
    {
        $totalPnL = 0.0;
        $totalFees = 0.0;
        $winCount = 0;
        $lossCount = 0;

        foreach ($trades as $trade) {
            $pnl = (float) ($trade['pnl'] ?? 0.0);
            $fees = (float) ($trade['fees_paid'] ?? 0.0);

            $totalPnL += $pnl;
            $totalFees += $fees;

            if ($pnl > 0) {
                $winCount++;
            } elseif ($pnl < 0) {
                $lossCount++;
            }
        }

        return [
            'total' => $totalPnL,
            'fees' => $totalFees,
            'win_count' => $winCount,
            'loss_count' => $lossCount,
            'trade_count' => count($trades),
            'win_rate' => count($trades) > 0 ? ($winCount / count($trades)) * 100 : 0,
        ];
    }

    /**
     * Açık pozisyonların gerçekleşmemiş PnL'ini hesapla
     */
    private function calculateUnrealizedPnL(array $openTrades, array $carryOverTrades): array
    {
        $allOpenTrades = array_merge($openTrades, $carryOverTrades);
        $totalUnrealizedPnL = 0.0;
        $totalFees = 0.0;

        foreach ($allOpenTrades as $trade) {
            // Mevcut fiyatı al (cache'den veya API'den)
            $currentPrice = $this->getCurrentPrice($trade['symbol']);
            
            if ($currentPrice) {
                $unrealizedPnL = $this->calculateUnrealizedPnLForTrade($trade, $currentPrice);
                $totalUnrealizedPnL += $unrealizedPnL;
            }

            $totalFees += (float) ($trade['fees_paid'] ?? 0.0);
        }

        return [
            'total' => $totalUnrealizedPnL,
            'fees' => $totalFees,
            'position_count' => count($allOpenTrades),
        ];
    }

    /**
     * Tek pozisyon için gerçekleşmemiş PnL hesapla
     */
    private function calculateUnrealizedPnLForTrade(array $trade, float $currentPrice): float
    {
        $entryPrice = (float) $trade['entry_price'];
        $qty = (float) $trade['qty'];
        $side = $trade['side'];

        if ($side === 'LONG') {
            return ($currentPrice - $entryPrice) * $qty;
        } else { // SHORT
            return ($entryPrice - $currentPrice) * $qty;
        }
    }

    /**
     * Mevcut fiyatı al
     */
    private function getCurrentPrice(string $symbol): ?float
    {
        $cacheKey = "current_price_{$symbol}";
        
        return Cache::remember($cacheKey, 60, function () use ($symbol) {
            try {
                // Bu gerçek implementasyonda BybitMarketData'dan gelecek
                // Şimdilik mock data
                $prices = [
                    'BTCUSDT' => 43250.0,
                    'ETHUSDT' => 2650.0,
                    'SOLUSDT' => 98.5,
                    'XRPUSDT' => 0.58,
                ];

                return $prices[$symbol] ?? null;
            } catch (\Throwable $e) {
                Log::error('Error fetching current price', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Günlük risk durumunu değerlendir
     */
    private function assessDailyRiskStatus(array $dailyPnL): string
    {
        $netPnL = $dailyPnL['pnl_breakdown']['net_pnl'] ?? 0.0;
        $targetProgress = $dailyPnL['target_analysis']['target_progress_pct'] ?? 0.0;

        if ($targetProgress >= 100) {
            return 'target_reached';
        } elseif ($targetProgress >= 80) {
            return 'near_target';
        } elseif ($targetProgress >= 50) {
            return 'on_track';
        } elseif ($netPnL >= 0) {
            return 'positive';
        } elseif ($netPnL >= -10) {
            return 'slight_loss';
        } else {
            return 'significant_loss';
        }
    }

    /**
     * Trading'e devam edilmeli mi?
     */
    private function shouldContinueTrading(array $dailyPnL): bool
    {
        $targetReached = $dailyPnL['target_analysis']['target_reached'] ?? false;
        $netPnL = $dailyPnL['pnl_breakdown']['net_pnl'] ?? 0.0;

        // Hedef ulaşıldı: Durabilir (konservatif yaklaşım)
        if ($targetReached) {
            return false;
        }

        // Büyük kayıp: Dur
        if ($netPnL < -50) { // $50+ kayıp
            return false;
        }

        // Devam et
        return true;
    }

    /**
     * AI için önerileri al
     */
    private function getAIRecommendation(array $dailyPnL): string
    {
        $riskStatus = $this->assessDailyRiskStatus($dailyPnL);
        $targetProgress = $dailyPnL['target_analysis']['target_progress_pct'] ?? 0.0;

        return match ($riskStatus) {
            'target_reached' => 'Daily target achieved. Consider taking profit and stopping for today.',
            'near_target' => "Close to target (" . number_format($targetProgress, 1) . "%). Be selective with new trades.",
            'on_track' => 'Good progress. Continue with current strategy.',
            'positive' => 'Positive but below target. Look for quality setups.',
            'slight_loss' => 'Minor loss. Stick to risk management rules.',
            'significant_loss' => 'Significant loss. Consider stopping trading for today.',
            default => 'Monitor performance closely.',
        };
    }

    /**
     * Kullanıcı risk profilini al
     */
    private function getUserRiskProfile(User $user): array
    {
        $profileName = $user->meta['risk_profile'] ?? 'moderate';
        $profiles = config('risk_profiles.profiles', []);
        
        return $profiles[$profileName] ?? $profiles['moderate'] ?? [];
    }

    /**
     * Haftalık PnL özeti
     */
    public function getWeeklyPnLSummary(User $user): array
    {
        $startOfWeek = now()->startOfWeek();
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'pnl_data' => $this->calculateDailyPnL($user, $date),
            ];
        }

        return [
            'week_start' => $startOfWeek->format('Y-m-d'),
            'user_id' => $user->id,
            'daily_breakdown' => $days,
            'generated_at' => now()->toISOString(),
        ];
    }
}
