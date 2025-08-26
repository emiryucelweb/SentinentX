<?php

declare(strict_types=1);

namespace App\Services\Lab;

use App\Models\LabMetric;
use App\Models\LabRun;
use App\Models\LabTrade;

final class PerformanceMonitorService
{
    /**
     * LAB run performansını izle ve uyarıları tetikle
     *
     * @return array<string, mixed>
     */
    public function monitorLabRun(int $labRunId): array
    {
        $labRun = LabRun::find($labRunId);
        if (! $labRun) {
            return ['error' => 'LAB run not found'];
        }

        $metrics = $this->calculateRunMetrics($labRun);
        $alerts = $this->checkPerformanceAlerts($metrics, $labRun);

        // Metrikleri kaydet
        LabMetric::updateOrCreate(
            ['lab_run_id' => $labRunId, 'as_of' => now()->toDateString()],
            [
                'equity' => $metrics['current_equity'],
                'pf' => $metrics['pf'],
                'maxdd_pct' => $metrics['maxdd_pct'],
                'sharpe' => $metrics['sharpe'],
                'win_rate' => $metrics['win_rate'],
                'avg_trade_pct' => $metrics['avg_trade_pct'],
                'meta' => json_encode($metrics, JSON_UNESCAPED_UNICODE),
            ]
        );

        return [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'status' => $this->determineRunStatus($metrics),
        ];
    }

    /**
     * LAB run metriklerini hesapla
     *
     * @return array<string, mixed>
     */
    private function calculateRunMetrics(LabRun $labRun): array
    {
        $trades = LabTrade::where('cycle_uuid', 'like', '%'.$labRun->id.'%')
            ->orderBy('closed_at')
            ->get();

        if ($trades->isEmpty()) {
            return [
                'current_equity' => $labRun->initial_equity,
                'pf' => 1.0,
                'maxdd_pct' => 0.0,
                'sharpe' => null,
                'win_rate' => 0.0,
                'avg_trade_pct' => 0.0,
                'total_trades' => 0,
                'winning_trades' => 0,
                'losing_trades' => 0,
            ];
        }

        $currentEquity = $labRun->initial_equity;
        $peak = $labRun->initial_equity;
        $maxDdPct = 0.0;
        $returns = [];
        $winningTrades = 0;
        $losingTrades = 0;

        foreach ($trades as $trade) {
            $pnlPct = (float) ($trade->pnl_pct ?? 0.0);
            $returns[] = $pnlPct / 100.0;

            $currentEquity *= (1.0 + $pnlPct / 100.0);

            if ($currentEquity > $peak) {
                $peak = $currentEquity;
            }

            $dd = ($peak > 0.0) ? (($peak - $currentEquity) / $peak) * 100.0 : 0.0;
            if ($dd > $maxDdPct) {
                $maxDdPct = $dd;
            }

            if ($pnlPct > 0) {
                $winningTrades++;
            } else {
                $losingTrades++;
            }
        }

        $totalTrades = $trades->count();
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100.0 : 0.0;
        $avgTradePct = $totalTrades > 0 ? array_sum($returns) * 100.0 / $totalTrades : 0.0;

        return [
            'current_equity' => $currentEquity,
            'pf' => $currentEquity / $labRun->initial_equity,
            'maxdd_pct' => $maxDdPct,
            'sharpe' => $this->calculateSharpe($returns),
            'win_rate' => $winRate,
            'avg_trade_pct' => $avgTradePct,
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
        ];
    }

    /**
     * Performans uyarılarını kontrol et
     *
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private function checkPerformanceAlerts(array $metrics, LabRun $labRun): array
    {
        $alerts = [];

        // Drawdown uyarısı
        if ($metrics['maxdd_pct'] > 15.0) {
            $alerts[] = [
                'level' => 'CRITICAL',
                'type' => 'HIGH_DRAWDOWN',
                'message' => 'Maximum drawdown exceeded 15% threshold',
                'value' => $metrics['maxdd_pct'],
                'threshold' => 15.0,
            ];
        } elseif ($metrics['maxdd_pct'] > 10.0) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'HIGH_DRAWDOWN',
                'message' => 'Maximum drawdown approaching 15% threshold',
                'value' => $metrics['maxdd_pct'],
                'threshold' => 15.0,
            ];
        }

        // Win rate uyarısı
        if ($metrics['win_rate'] < 40.0) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'LOW_WIN_RATE',
                'message' => 'Win rate below 40% threshold',
                'value' => $metrics['win_rate'],
                'threshold' => 40.0,
            ];
        }

        // Profit factor uyarısı
        if ($metrics['pf'] < 0.95) {
            $alerts[] = [
                'level' => 'CRITICAL',
                'type' => 'LOW_PROFIT_FACTOR',
                'message' => 'Profit factor below 0.95 threshold',
                'value' => $metrics['pf'],
                'threshold' => 0.95,
            ];
        }

        // Sharpe ratio uyarısı
        if ($metrics['sharpe'] !== null && $metrics['sharpe'] < 0.5) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'LOW_SHARPE',
                'message' => 'Sharpe ratio below 0.5 threshold',
                'value' => $metrics['sharpe'],
                'threshold' => 0.5,
            ];
        }

        // Trade sayısı uyarısı
        if ($metrics['total_trades'] < 10) {
            $alerts[] = [
                'level' => 'INFO',
                'type' => 'LOW_TRADE_COUNT',
                'message' => 'Low trade count for reliable metrics',
                'value' => $metrics['total_trades'],
                'threshold' => 10,
            ];
        }

        return $alerts;
    }

    /**
     * LAB run durumunu belirle
     *
     * @param  array<string, mixed>  $metrics
     */
    private function determineRunStatus(array $metrics): string
    {
        if ($metrics['pf'] < 0.90) {
            return 'CRITICAL';
        }
        if ($metrics['pf'] < 0.95 || $metrics['maxdd_pct'] > 15.0) {
            return 'WARNING';
        }
        if ($metrics['pf'] > 1.05 && $metrics['win_rate'] > 50.0) {
            return 'EXCELLENT';
        }

        return 'NORMAL';
    }

    /**
     * Sharpe ratio hesapla
     *
     * @param  array<int, float>  $returns
     */
    private function calculateSharpe(array $returns): ?float
    {
        $n = count($returns);
        if ($n < 2) {
            return null;
        }

        $mean = array_sum($returns) / $n;
        $variance = 0.0;

        foreach ($returns as $r) {
            $variance += ($r - $mean) ** 2;
        }
        $variance /= $n;

        $std = sqrt($variance);
        if ($std == 0.0) {
            return null;
        }

        return $mean / $std * sqrt($n);
    }

    /**
     * LAB run özet raporu oluştur
     *
     * @return array<string, mixed>
     */
    public function generateRunSummary(int $labRunId): array
    {
        $labRun = LabRun::find($labRunId);
        if (! $labRun) {
            return ['error' => 'LAB run not found'];
        }

        $metrics = $this->calculateRunMetrics($labRun);
        $dailyMetrics = $this->getDailyMetrics($labRunId);

        return [
            'run_info' => [
                'id' => $labRun->id,
                'start_date' => $labRun->start_date,
                'end_date' => $labRun->end_date,
                'symbols' => json_decode((string) $labRun->symbols, true),
                'initial_equity' => $labRun->initial_equity,
                'status' => $labRun->status,
            ],
            'performance_summary' => [
                'current_equity' => $metrics['current_equity'],
                'total_return_pct' => (($metrics['current_equity'] - $labRun->initial_equity) / $labRun->initial_equity) * 100.0,
                'profit_factor' => $metrics['pf'],
                'max_drawdown_pct' => $metrics['maxdd_pct'],
                'sharpe_ratio' => $metrics['sharpe'],
                'win_rate' => $metrics['win_rate'],
                'total_trades' => $metrics['total_trades'],
                'avg_trade_pct' => $metrics['avg_trade_pct'],
            ],
            'daily_metrics' => $dailyMetrics,
            'risk_assessment' => $this->assessRisk($metrics),
            'recommendations' => $this->generateRecommendations($metrics),
        ];
    }

    /**
     * Günlük metrikleri al
     */
    private function getDailyMetrics(int $labRunId): array
    {
        return LabMetric::where('lab_run_id', $labRunId)
            ->orderBy('date')
            ->get()
            ->map(function ($metric) {
                return [
                    'date' => $metric->date,
                    'equity' => $metric->equity,
                    'pf' => $metric->pf,
                    'maxdd_pct' => $metric->maxdd_pct,
                    'sharpe' => $metric->sharpe,
                    'win_rate' => $metric->win_rate,
                ];
            })
            ->toArray();
    }

    /**
     * Risk değerlendirmesi yap
     */
    private function assessRisk(array $metrics): array
    {
        $riskScore = 0;
        $riskFactors = [];

        if ($metrics['maxdd_pct'] > 15.0) {
            $riskScore += 3;
            $riskFactors[] = 'High maximum drawdown';
        }
        if ($metrics['win_rate'] < 40.0) {
            $riskScore += 2;
            $riskFactors[] = 'Low win rate';
        }
        if ($metrics['pf'] < 0.95) {
            $riskScore += 3;
            $riskFactors[] = 'Low profit factor';
        }
        if ($metrics['sharpe'] !== null && $metrics['sharpe'] < 0.5) {
            $riskScore += 1;
            $riskFactors[] = 'Low Sharpe ratio';
        }

        $riskLevel = match (true) {
            $riskScore >= 6 => 'HIGH',
            $riskScore >= 3 => 'MEDIUM',
            default => 'LOW',
        };

        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Öneriler oluştur
     */
    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        if ($metrics['maxdd_pct'] > 15.0) {
            $recommendations[] = 'Consider reducing position sizes to limit drawdown';
            $recommendations[] = 'Review stop-loss and risk management parameters';
        }

        if ($metrics['win_rate'] < 40.0) {
            $recommendations[] = 'Review entry criteria and filtering conditions';
            $recommendations[] = 'Consider adjusting confidence thresholds';
        }

        if ($metrics['pf'] < 0.95) {
            $recommendations[] = 'Review exit strategies and take-profit levels';
            $recommendations[] = 'Consider implementing trailing stops';
        }

        if ($metrics['sharpe'] !== null && $metrics['sharpe'] < 0.5) {
            $recommendations[] = 'Review risk-reward ratios for trades';
            $recommendations[] = 'Consider position sizing optimization';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Performance metrics are within acceptable ranges';
            $recommendations[] = 'Continue monitoring for any degradation';
        }

        return $recommendations;
    }
}
