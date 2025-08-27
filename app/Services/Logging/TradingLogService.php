<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradingLogService
{
    /**
     * AI kararını logla
     */
    public function logAiDecision(array $aiDecision, array $context, User $user): void
    {
        try {
            DB::table('ai_decision_logs')->insert([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'symbol' => $context['symbol'] ?? null,
                'decision_type' => $context['decision_type'] ?? 'position_open',
                'ai_provider' => $aiDecision['provider'] ?? 'consensus',
                'decision' => $aiDecision['action'] ?? $aiDecision['decision'] ?? null,
                'confidence' => $aiDecision['confidence'] ?? null,
                'leverage' => $aiDecision['leverage'] ?? null,
                'stop_loss' => $aiDecision['stop_loss'] ?? null,
                'take_profit' => $aiDecision['take_profit'] ?? null,
                'reason' => $aiDecision['reason'] ?? null,
                'market_price' => $context['price'] ?? null,
                'coingecko_score' => $aiDecision['coingecko_score'] ?? null,
                'market_sentiment' => $aiDecision['market_sentiment'] ?? null,
                'risk_profile' => $user->meta['risk_profile'] ?? 'moderate',
                'context_data' => json_encode($context),
                'ai_response' => json_encode($aiDecision),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('AI decision logged', [
                'user_id' => $user->id,
                'symbol' => $context['symbol'] ?? null,
                'decision' => $aiDecision['action'] ?? $aiDecision['decision'] ?? null,
                'confidence' => $aiDecision['confidence'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to log AI decision', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'ai_decision' => $aiDecision,
            ]);
        }
    }

    /**
     * Pozisyon açılışını logla
     */
    public function logPositionOpen(Trade $trade, array $aiDecision, array $executionData): void
    {
        try {
            DB::table('position_logs')->insert([
                'trade_id' => $trade->id,
                'user_id' => $trade->user_id,
                'tenant_id' => $trade->tenant_id,
                'symbol' => $trade->symbol,
                'action' => 'OPEN',
                'side' => $trade->side,
                'entry_price' => $trade->entry_price,
                'qty' => $trade->qty,
                'leverage' => $trade->leverage,
                'stop_loss' => $trade->stop_loss,
                'take_profit' => $trade->take_profit,
                'ai_confidence' => $aiDecision['confidence'] ?? null,
                'ai_reason' => $aiDecision['reason'] ?? null,
                'execution_price' => $executionData['fill_price'] ?? $trade->entry_price,
                'execution_fee' => $executionData['fee'] ?? 0.0,
                'slippage' => $this->calculateSlippage(
                    $aiDecision['expected_price'] ?? $trade->entry_price,
                    $executionData['fill_price'] ?? $trade->entry_price
                ),
                'bybit_order_id' => $trade->bybit_order_id,
                'risk_profile' => $trade->meta['risk_profile'] ?? 'moderate',
                'market_conditions' => json_encode($executionData['market_conditions'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Trade tablosuna da execution detaylarını ekle
            $trade->update([
                'fees_paid' => ($trade->fees_paid ?? 0.0) + ($executionData['fee'] ?? 0.0),
                'meta' => array_merge($trade->meta ?? [], [
                    'execution_data' => $executionData,
                    'ai_decision' => $aiDecision,
                    'logged_at' => now()->toISOString(),
                ]),
            ]);

            Log::info('Position open logged', [
                'trade_id' => $trade->id,
                'symbol' => $trade->symbol,
                'side' => $trade->side,
                'entry_price' => $trade->entry_price,
                'qty' => $trade->qty,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to log position open', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pozisyon kapanışını logla
     */
    public function logPositionClose(Trade $trade, array $closeData, string $closeReason): void
    {
        try {
            $pnl = $this->calculateFinalPnL($trade, $closeData);

            DB::table('position_logs')->insert([
                'trade_id' => $trade->id,
                'user_id' => $trade->user_id,
                'tenant_id' => $trade->tenant_id,
                'symbol' => $trade->symbol,
                'action' => 'CLOSE',
                'side' => $trade->side,
                'entry_price' => $trade->entry_price,
                'exit_price' => $closeData['fill_price'] ?? $closeData['price'] ?? null,
                'qty' => $trade->qty,
                'pnl' => $pnl['pnl'],
                'pnl_percentage' => $pnl['pnl_percentage'],
                'execution_fee' => $closeData['fee'] ?? 0.0,
                'total_fees' => ($trade->fees_paid ?? 0.0) + ($closeData['fee'] ?? 0.0),
                'close_reason' => $closeReason,
                'duration_minutes' => $this->calculateDuration($trade->created_at, now()),
                'bybit_order_id' => $closeData['order_id'] ?? null,
                'market_conditions' => json_encode($closeData['market_conditions'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Trade tablosunu güncelle
            $trade->update([
                'status' => 'CLOSED',
                'exit_price' => $closeData['fill_price'] ?? $closeData['price'] ?? null,
                'pnl' => $pnl['pnl'],
                'fees_paid' => ($trade->fees_paid ?? 0.0) + ($closeData['fee'] ?? 0.0),
                'closed_at' => now(),
                'meta' => array_merge($trade->meta ?? [], [
                    'close_data' => $closeData,
                    'close_reason' => $closeReason,
                    'final_pnl' => $pnl,
                    'duration_minutes' => $this->calculateDuration($trade->created_at, now()),
                ]),
            ]);

            Log::info('Position close logged', [
                'trade_id' => $trade->id,
                'symbol' => $trade->symbol,
                'pnl' => $pnl['pnl'],
                'pnl_percentage' => $pnl['pnl_percentage'],
                'close_reason' => $closeReason,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to log position close', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pozisyon güncelleme logla (SL/TP değişiklikleri)
     */
    public function logPositionUpdate(Trade $trade, array $updateData, string $updateReason): void
    {
        try {
            DB::table('position_logs')->insert([
                'trade_id' => $trade->id,
                'user_id' => $trade->user_id,
                'tenant_id' => $trade->tenant_id,
                'symbol' => $trade->symbol,
                'action' => 'UPDATE',
                'side' => $trade->side,
                'entry_price' => $trade->entry_price,
                'qty' => $trade->qty,
                'old_stop_loss' => $trade->stop_loss,
                'new_stop_loss' => $updateData['new_stop_loss'] ?? null,
                'old_take_profit' => $trade->take_profit,
                'new_take_profit' => $updateData['new_take_profit'] ?? null,
                'update_reason' => $updateReason,
                'ai_confidence' => $updateData['ai_confidence'] ?? null,
                'market_price' => $updateData['current_price'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Trade tablosunu güncelle
            $updateFields = [];
            if (isset($updateData['new_stop_loss'])) {
                $updateFields['stop_loss'] = $updateData['new_stop_loss'];
            }
            if (isset($updateData['new_take_profit'])) {
                $updateFields['take_profit'] = $updateData['new_take_profit'];
            }

            if (! empty($updateFields)) {
                $updateFields['meta'] = array_merge($trade->meta ?? [], [
                    'last_update' => $updateData,
                    'last_update_reason' => $updateReason,
                    'updated_at' => now()->toISOString(),
                ]);

                $trade->update($updateFields);
            }

            Log::info('Position update logged', [
                'trade_id' => $trade->id,
                'symbol' => $trade->symbol,
                'update_reason' => $updateReason,
                'updates' => $updateFields,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to log position update', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Performans raporunu oluştur
     *
     * @return array<string, mixed>
     */
    public function generatePerformanceReport(User $user, Carbon $startDate, Carbon $endDate): array
    {
        try {
            // Temel trade istatistikleri
            $trades = DB::table('position_logs')
                ->where('user_id', $user->id)
                ->where('action', 'CLOSE')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $totalTrades = $trades->count();
            $winningTrades = $trades->where('pnl', '>', 0)->count();
            $losingTrades = $trades->where('pnl', '<', 0)->count();
            $totalPnL = $trades->sum('pnl');
            $totalFees = $trades->sum('total_fees');
            $netPnL = $totalPnL - $totalFees;

            // AI performansı
            $aiDecisions = DB::table('ai_decision_logs')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $avgConfidence = $aiDecisions->avg('confidence');
            $aiProviderStats = $aiDecisions->groupBy('ai_provider')
                ->map(function ($decisions) {
                    return [
                        'count' => $decisions->count(),
                        'avg_confidence' => $decisions->avg('confidence'),
                    ];
                });

            // Sembol bazlı performans
            $symbolStats = $trades->groupBy('symbol')
                ->map(function ($symbolTrades) {
                    return [
                        'trade_count' => $symbolTrades->count(),
                        'total_pnl' => $symbolTrades->sum('pnl'),
                        'win_rate' => $symbolTrades->where('pnl', '>', 0)->count() / $symbolTrades->count() * 100,
                        'avg_duration' => $symbolTrades->avg('duration_minutes'),
                    ];
                });

            return [
                'period' => [
                    'start' => $startDate->toISOString(),
                    'end' => $endDate->toISOString(),
                    'days' => $startDate->diffInDays($endDate),
                ],
                'trading_performance' => [
                    'total_trades' => $totalTrades,
                    'winning_trades' => $winningTrades,
                    'losing_trades' => $losingTrades,
                    'win_rate' => $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0,
                    'total_pnl' => $totalPnL,
                    'total_fees' => $totalFees,
                    'net_pnl' => $netPnL,
                    'avg_pnl_per_trade' => $totalTrades > 0 ? $netPnL / $totalTrades : 0,
                    'profit_factor' => $this->calculateProfitFactor($trades),
                    'max_drawdown' => $this->calculateMaxDrawdown($trades),
                ],
                'ai_performance' => [
                    'total_decisions' => $aiDecisions->count(),
                    'avg_confidence' => $avgConfidence,
                    'provider_stats' => $aiProviderStats,
                ],
                'symbol_performance' => $symbolStats,
                'generated_at' => now()->toISOString(),
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to generate performance report', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Report generation failed'];
        }
    }

    /**
     * Slippage hesapla
     */
    private function calculateSlippage(float $expectedPrice, float $actualPrice): float
    {
        if ($expectedPrice <= 0) {
            return 0.0;
        }

        return abs(($actualPrice - $expectedPrice) / $expectedPrice) * 100;
    }

    /**
     * Final PnL hesapla
     */
    private function calculateFinalPnL(Trade $trade, array $closeData): array
    {
        $entryPrice = (float) $trade->entry_price;
        $exitPrice = (float) ($closeData['fill_price'] ?? $closeData['price'] ?? 0.0);
        $qty = (float) $trade->qty;

        if ($trade->side === 'LONG') {
            $pnl = ($exitPrice - $entryPrice) * $qty;
            $pnlPercentage = (($exitPrice - $entryPrice) / $entryPrice) * 100;
        } else { // SHORT
            $pnl = ($entryPrice - $exitPrice) * $qty;
            $pnlPercentage = (($entryPrice - $exitPrice) / $entryPrice) * 100;
        }

        return [
            'pnl' => $pnl,
            'pnl_percentage' => $pnlPercentage,
            'entry_price' => $entryPrice,
            'exit_price' => $exitPrice,
            'qty' => $qty,
        ];
    }

    /**
     * Süre hesapla (dakika)
     */
    private function calculateDuration(Carbon $start, Carbon $end): int
    {
        return $start->diffInMinutes($end);
    }

    /**
     * Profit factor hesapla
     */
    private function calculateProfitFactor($trades): float
    {
        $grossProfit = $trades->where('pnl', '>', 0)->sum('pnl');
        $grossLoss = abs($trades->where('pnl', '<', 0)->sum('pnl'));

        if ($grossLoss <= 0) {
            return $grossProfit > 0 ? INF : 0.0;
        }

        return $grossProfit / $grossLoss;
    }

    /**
     * Max drawdown hesapla
     */
    private function calculateMaxDrawdown($trades): float
    {
        $equity = 0.0;
        $peak = 0.0;
        $maxDrawdown = 0.0;

        foreach ($trades->sortBy('created_at') as $trade) {
            $equity += $trade->pnl;

            if ($equity > $peak) {
                $peak = $equity;
            }

            $drawdown = (($peak - $equity) / $peak) * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return $maxDrawdown;
    }
}
