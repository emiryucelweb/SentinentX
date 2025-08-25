<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LabRun;
use App\Services\Lab\PerformanceMonitorService;
use Illuminate\Console\Command;

final class LabMonitorCommand extends Command
{
    protected $signature = 'sentx:lab-monitor 
        {--run-id= : Specific LAB run ID to monitor}
        {--active : Monitor only active runs}
        {--json : Output in JSON format}';

    protected $description = 'LAB run performansını izle ve uyarıları göster';

    public function handle(PerformanceMonitorService $monitor): int
    {
        $runId = $this->option('run-id');
        $activeOnly = (bool) $this->option('active');
        $jsonOutput = (bool) $this->option('json');

        if ($runId) {
            // Belirli bir run'ı izle
            $result = $monitor->monitorLabRun((int) $runId);
            if (isset($result['error'])) {
                $this->error($result['error']);

                return self::FAILURE;
            }

            if ($jsonOutput) {
                $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->displayRunStatus($result);
            }

            return self::SUCCESS;
        }

        // Tüm aktif run'ları listele ve izle
        $query = LabRun::query();
        if ($activeOnly) {
            $query->where('status', 'RUNNING');
        }

        $runs = $query->orderBy('created_at', 'desc')->get();

        if ($runs->isEmpty()) {
            $this->info('No LAB runs found.');

            return self::SUCCESS;
        }

        $this->info('🔍 LAB Run Monitoring');
        $this->line('');

        foreach ($runs as $run) {
            $result = $monitor->monitorLabRun($run->id);

            if ($jsonOutput) {
                $this->line("=== Run ID: {$run->id} ===");
                $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->line('');
            } else {
                $this->displayRunSummary($run, $result);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayRunStatus(array $result): void
    {
        $metrics = $result['metrics'];
        $alerts = $result['alerts'];
        $status = $result['status'];

        $this->info('📊 LAB Run Status: '.$status);
        $this->line('');

        // Metrikler
        $this->info('Performance Metrics:');
        $this->line('  Current Equity: $'.number_format($metrics['current_equity'], 2));
        $this->line('  Profit Factor: '.round($metrics['pf'], 4));
        $this->line('  Max Drawdown: '.round($metrics['maxdd_pct'], 2).'%');
        $this->line('  Sharpe Ratio: '.($metrics['sharpe'] ? round($metrics['sharpe'], 3) : 'N/A'));
        $this->line('  Win Rate: '.round($metrics['win_rate'], 1).'%');
        $this->line('  Total Trades: '.$metrics['total_trades']);
        $this->line('  Avg Trade: '.round($metrics['avg_trade_pct'], 2).'%');
        $this->line('');

        // Uyarılar
        if (! empty($alerts)) {
            $this->warn('⚠️  Performance Alerts:');
            foreach ($alerts as $alert) {
                $icon = match ($alert['level']) {
                    'CRITICAL' => '🚨',
                    'WARNING' => '⚠️',
                    'INFO' => 'ℹ️',
                    default => '📝',
                };
                $this->line("  {$icon} [{$alert['level']}] {$alert['message']}");
            }
            $this->line('');
        } else {
            $this->info('✅ No performance alerts');
            $this->line('');
        }
    }

    /**
     * @param mixed $run
     * @param array<string, mixed> $result
     */
    private function displayRunSummary($run, array $result): void
    {
        $metrics = $result['metrics'];
        $status = $result['status'];

        $statusIcon = match ($status) {
            'CRITICAL' => '🚨',
            'WARNING' => '⚠️',
            'EXCELLENT' => '🌟',
            default => '📊',
        };

        $this->line("{$statusIcon} Run #{$run->id} - {$run->status}");
        $this->line('  Symbols: '.implode(', ', json_decode($run->symbols, true)));
        $this->line('  Initial Equity: $'.number_format((float) $run->initial_equity, 2));
        $this->line('  Current Equity: $'.number_format($metrics['current_equity'], 2));
        $this->line('  PF: '.round($metrics['pf'], 4).
                   ', MaxDD: '.round($metrics['maxdd_pct'], 2).'%'.
                   ', Win Rate: '.round($metrics['win_rate'], 1).'%');
        $this->line('  Trades: '.$metrics['total_trades'].
                   ' (W: '.$metrics['winning_trades'].
                   ', L: '.$metrics['losing_trades'].')');
        $this->line('');
    }
}
