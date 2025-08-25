<?php

namespace App\Services\Risk;

use App\Models\Trade;
use Illuminate\Support\Facades\Log;

class PerformanceGate
{
    private const MIN_PROFIT_FACTOR = 1.2;

    private const MIN_SHARPE_RATIO = 0.5;

    private const MAX_DRAWDOWN_PCT = 15.0;

    private const MIN_TRADES_FOR_EVALUATION = 10;

    public function calculateMetrics(int $days = 30): array
    {
        $trades = Trade::where('status', 'CLOSED')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($trades->count() < self::MIN_TRADES_FOR_EVALUATION) {
            return [
                'profit_factor' => 0.0,
                'sharpe_ratio' => 0.0,
                'max_drawdown_pct' => 0.0,
                'total_trades' => $trades->count(),
                'insufficient_data' => true,
            ];
        }

        $wins = $trades->where('pnl', '>', 0);
        $losses = $trades->where('pnl', '<', 0);

        $totalWins = $wins->sum('pnl');
        $totalLosses = abs($losses->sum('pnl'));

        $profitFactor = $totalLosses > 0 ? $totalWins / $totalLosses : ($totalWins > 0 ? 999 : 0);

        $returns = $trades->pluck('pnl')->toArray();
        $sharpeRatio = $this->calculateSharpeRatio($returns);
        $maxDrawdownPct = $this->calculateMaxDrawdown($returns);

        return [
            'profit_factor' => round($profitFactor, 3),
            'sharpe_ratio' => round($sharpeRatio, 3),
            'max_drawdown_pct' => round($maxDrawdownPct, 2),
            'total_trades' => $trades->count(),
            'winning_trades' => $wins->count(),
            'losing_trades' => $losses->count(),
            'total_wins' => round($totalWins, 2),
            'total_losses' => round($totalLosses, 2),
            'win_rate' => $trades->count() > 0 ? round(($wins->count() / $trades->count()) * 100, 1) : 0,
            'avg_win' => $wins->count() > 0 ? round($totalWins / $wins->count(), 2) : 0,
            'avg_loss' => $losses->count() > 0 ? round($totalLosses / $losses->count(), 2) : 0,
            'period_days' => $days,
            'insufficient_data' => false,
        ];
    }

    public function shouldAllowTrading(array $metrics): array
    {
        if ($metrics['insufficient_data'] ?? false) {
            return [
                'allowed' => true,
                'reason' => 'INSUFFICIENT_DATA_ALLOW_TRADING',
                'failed_criteria' => [],
            ];
        }

        $failedCriteria = [];

        if ($metrics['profit_factor'] < self::MIN_PROFIT_FACTOR) {
            $failedCriteria[] = 'profit_factor';
        }

        if ($metrics['sharpe_ratio'] < self::MIN_SHARPE_RATIO) {
            $failedCriteria[] = 'sharpe_ratio';
        }

        if ($metrics['max_drawdown_pct'] > self::MAX_DRAWDOWN_PCT) {
            $failedCriteria[] = 'max_drawdown';
        }

        $allowed = empty($failedCriteria);

        if (! $allowed) {
            Log::warning('Performance gate triggered self-brake', [
                'failed_criteria' => $failedCriteria,
                'metrics' => $metrics,
            ]);
        }

        return [
            'allowed' => $allowed,
            'reason' => $allowed ? 'PERFORMANCE_OK' : 'SELF_BRAKE',
            'failed_criteria' => $failedCriteria,
            'thresholds' => [
                'min_profit_factor' => self::MIN_PROFIT_FACTOR,
                'min_sharpe_ratio' => self::MIN_SHARPE_RATIO,
                'max_drawdown_pct' => self::MAX_DRAWDOWN_PCT,
            ],
        ];
    }

    private function calculateSharpeRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = 0;

        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }

        $stdDev = sqrt($variance / count($returns));

        return $stdDev > 0 ? $mean / $stdDev : 0.0;
    }

    private function calculateMaxDrawdown(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $cumulative = 0;
        $peak = 0;
        $maxDrawdown = 0;

        foreach ($returns as $return) {
            $cumulative += $return;
            $peak = max($peak, $cumulative);
            $drawdown = $peak - $cumulative;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $peak > 0 ? ($maxDrawdown / $peak) * 100 : 0.0;
    }
}
