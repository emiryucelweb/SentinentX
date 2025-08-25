<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Models\Trade;
use App\Services\Notifier\AlertDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Performance Gates (PF/Sharpe) with Trading Halt
 * PF<1.1 or Sharpe<0.5 → open=STOP, manage-only mode
 */
class PerformanceGates
{
    private const PF_THRESHOLD = 1.1;

    private const SHARPE_THRESHOLD = 0.5;

    private const EVALUATION_WINDOW_DAYS = 30;

    private const HALT_DURATION_HOURS = 4;

    public function __construct(
        private AlertDispatcher $alertDispatcher
    ) {}

    /**
     * Check performance gates before opening new positions
     */
    /**
     * @return array<string, mixed>
     */
    public function checkGatesForOpen(int $userId, string $symbol = 'ALL'): array
    {
        $windowStart = now()->subDays(self::EVALUATION_WINDOW_DAYS);

        // Get trades in evaluation window
        $trades = Trade::where('user_id', $userId)
            ->where('status', 'closed')
            ->where('closed_at', '>=', $windowStart)
            ->when($symbol !== 'ALL', fn ($q) => $q->where('symbol', $symbol))
            ->orderBy('closed_at')
            ->get();

        if ($trades->count() < 10) {
            return [
                'gate_status' => 'INSUFFICIENT_DATA',
                'can_open' => true,
                'reason' => 'Less than 10 closed trades in evaluation window',
                'trades_count' => $trades->count(),
                'window_days' => self::EVALUATION_WINDOW_DAYS,
            ];
        }

        // Calculate performance metrics
        $metrics = $this->calculatePerformanceMetrics($trades);

        // Check gates
        $pfGate = $metrics['profit_factor'] >= self::PF_THRESHOLD;
        $sharpeGate = $metrics['sharpe_ratio'] >= self::SHARPE_THRESHOLD;

        $gatesPassed = $pfGate && $sharpeGate;

        // Log gate check
        Log::info('Performance gates checked', [
            'user_id' => $userId,
            'symbol' => $symbol,
            'profit_factor' => $metrics['profit_factor'],
            'sharpe_ratio' => $metrics['sharpe_ratio'],
            'pf_threshold' => self::PF_THRESHOLD,
            'sharpe_threshold' => self::SHARPE_THRESHOLD,
            'pf_gate_passed' => $pfGate,
            'sharpe_gate_passed' => $sharpeGate,
            'overall_gate_status' => $gatesPassed ? 'PASSED' : 'FAILED',
            'trades_evaluated' => $trades->count(),
            'evaluation_window_days' => self::EVALUATION_WINDOW_DAYS,
        ]);

        if (! $gatesPassed) {
            // Trigger trading halt
            $this->triggerTradingHalt($userId, $symbol, $metrics);

            return [
                'gate_status' => 'FAILED',
                'can_open' => false,
                'reason' => $this->buildFailureReason($pfGate, $sharpeGate, $metrics),
                'metrics' => $metrics,
                'halt_until' => now()->addHours(self::HALT_DURATION_HOURS),
                'runbook_url' => config('trading.runbooks.performance_gates'),
            ];
        }

        return [
            'gate_status' => 'PASSED',
            'can_open' => true,
            'metrics' => $metrics,
            'next_evaluation' => now()->addHours(1),
        ];
    }

    /**
     * Calculate performance metrics
     * @param \\Illuminate\\Support\\Collection<int, \\App\\Models\\Trade> $trades
     * @return array<string, mixed>
     */
    private function calculatePerformanceMetrics(\Illuminate\Support\Collection $trades): array
    {
        $totalPnl = $trades->sum('pnl');
        $winningTrades = $trades->where('pnl', '>', 0);
        $losingTrades = $trades->where('pnl', '<', 0);

        $grossProfit = $winningTrades->sum('pnl');
        $grossLoss = abs($losingTrades->sum('pnl'));

        // Profit Factor = Gross Profit / Gross Loss
        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? 999 : 0);

        // Sharpe Ratio calculation
        $returns = $trades->pluck('pnl')->toArray();
        $avgReturn = count($returns) > 0 ? array_sum($returns) / count($returns) : 0;
        $variance = $this->calculateVariance($returns, $avgReturn);
        $stdDev = sqrt($variance);
        $sharpeRatio = $stdDev > 0 ? $avgReturn / $stdDev : 0;

        // Win rate
        $winRate = $trades->count() > 0 ? $winningTrades->count() / $trades->count() : 0;

        // Average win/loss
        $avgWin = $winningTrades->count() > 0 ? $winningTrades->avg('pnl') : 0;
        $avgLoss = $losingTrades->count() > 0 ? $losingTrades->avg('pnl') : 0;

        return [
            'profit_factor' => round($profitFactor, 3),
            'sharpe_ratio' => round($sharpeRatio, 3),
            'total_pnl' => round($totalPnl, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_loss' => round($grossLoss, 2),
            'win_rate' => round($winRate, 3),
            'avg_win' => round($avgWin, 2),
            'avg_loss' => round($avgLoss, 2),
            'total_trades' => $trades->count(),
            'winning_trades' => $winningTrades->count(),
            'losing_trades' => $losingTrades->count(),
            'evaluation_period' => [
                'start' => $trades->first()?->closed_at,
                'end' => $trades->last()?->closed_at,
                'days' => self::EVALUATION_WINDOW_DAYS,
            ],
        ];
    }

    /**
     * Calculate variance for Sharpe ratio
     */
    private function calculateVariance(array $values, float $mean): float
    {
        if (empty($values)) {
            return 0;
        }

        $sum = 0;
        foreach ($values as $value) {
            $sum += pow($value - $mean, 2);
        }

        return $sum / count($values);
    }

    /**
     * Trigger trading halt and alerts
     */
    private function triggerTradingHalt(int $userId, string $symbol, array $metrics): void
    {
        $haltKey = "trading_halt:user_{$userId}:symbol_{$symbol}";
        $haltUntil = now()->addHours(self::HALT_DURATION_HOURS);

        // Set halt flag in cache
        Cache::put($haltKey, [
            'halted_at' => now()->toISOString(),
            'halt_until' => $haltUntil->toISOString(),
            'reason' => 'PERFORMANCE_GATES_FAILED',
            'metrics' => $metrics,
        ], now()->addHours(self::HALT_DURATION_HOURS + 1));

        // Dispatch critical alert
        $this->alertDispatcher->send(
            'critical',
            'PERFORMANCE_GATES',
            "Trading halted for user {$userId} due to poor performance metrics",
            [
                'user_id' => $userId,
                'symbol' => $symbol,
                'profit_factor' => $metrics['profit_factor'],
                'sharpe_ratio' => $metrics['sharpe_ratio'],
                'required_pf' => self::PF_THRESHOLD,
                'required_sharpe' => self::SHARPE_THRESHOLD,
                'halt_duration_hours' => self::HALT_DURATION_HOURS,
                'halt_until' => $haltUntil->toISOString(),
                'total_trades_evaluated' => $metrics['total_trades'],
                'evaluation_window_days' => self::EVALUATION_WINDOW_DAYS,
            ],
            "performance_halt_user_{$userId}_symbol_{$symbol}"
        );

        Log::critical('Trading halt triggered by performance gates', [
            'user_id' => $userId,
            'symbol' => $symbol,
            'halt_until' => $haltUntil->toISOString(),
            'metrics' => $metrics,
            'runbook_url' => config('trading.runbooks.performance_gates'),
        ]);
    }

    /**
     * Build failure reason message
     */
    private function buildFailureReason(bool $pfGate, bool $sharpeGate, array $metrics): string
    {
        $reasons = [];

        if (! $pfGate) {
            $reasons[] = sprintf(
                'Profit Factor %.3f < %.1f (required)',
                $metrics['profit_factor'],
                self::PF_THRESHOLD
            );
        }

        if (! $sharpeGate) {
            $reasons[] = sprintf(
                'Sharpe Ratio %.3f < %.1f (required)',
                $metrics['sharpe_ratio'],
                self::SHARPE_THRESHOLD
            );
        }

        return 'Performance gates failed: '.implode(', ', $reasons);
    }

    /**
     * Check if user is currently halted
     */
    public function isHalted(int $userId, string $symbol = 'ALL'): bool
    {
        $haltKey = "trading_halt:user_{$userId}:symbol_{$symbol}";

        return Cache::has($haltKey);
    }

    /**
     * Get halt status
     */
    public function getHaltStatus(int $userId, string $symbol = 'ALL'): ?array
    {
        $haltKey = "trading_halt:user_{$userId}:symbol_{$symbol}";

        return Cache::get($haltKey);
    }

    /**
     * Manually lift trading halt (emergency override)
     */
    public function liftHalt(int $userId, string $symbol = 'ALL', string $reason = 'Manual override'): bool
    {
        $haltKey = "trading_halt:user_{$userId}:symbol_{$symbol}";

        if (! Cache::has($haltKey)) {
            return false;
        }

        Cache::forget($haltKey);

        Log::warning('Trading halt manually lifted', [
            'user_id' => $userId,
            'symbol' => $symbol,
            'reason' => $reason,
            'lifted_at' => now()->toISOString(),
        ]);

        return true;
    }
}
