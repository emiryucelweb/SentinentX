<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Lab\MetricsServiceInterface;
use App\Contracts\Notifier\AlertDispatcher;
use App\Models\LabMetric;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class EodMetrics extends Command
{
    protected $signature = 'sentx:eod-metrics {--date=}';

    protected $description = 'Gün sonu LAB metriklerini (NET/GROSS PF, MaxDD, Sharpe) hesaplar, '
        .'kaydeder ve acceptance alert gönderir.';

    public function handle(MetricsServiceInterface $metrics, AlertDispatcher $alerts): int
    {
        $dateOpt = $this->option('date');
        $day = $dateOpt ? CarbonImmutable::parse((string) $dateOpt) : CarbonImmutable::now();
        $dateString = $day->toDateString();

        $initialEquity = (float) config('lab.initial_equity', 10000.0);
        $out = $metrics->computeDaily($day, $initialEquity);

        // DB-agnostik UPSERT — yeni alanlar meta içine yazılır (schema değişikliği gerektirmez)
        LabMetric::upsert([[
            'as_of' => $dateString,
            'pf' => $out['pf'],        // NET
            'maxdd_pct' => $out['maxdd_pct'],
            'sharpe' => $out['sharpe'],
            'meta' => json_encode([
                'initial_equity' => $initialEquity,
                'pf_gross' => $out['pf_gross'],
                'trades' => $out['trades'],
                'avg_trade_net_pct' => $out['avg_trade_net_pct'],
                'avg_trade_gross_pct' => $out['avg_trade_gross_pct'],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['as_of'], ['pf', 'maxdd_pct', 'sharpe', 'meta', 'updated_at']);

        // CLI çıktısı
        $payload = [
            'as_of' => $dateString,
            'pf' => $out['pf'],
            'pf_gross' => $out['pf_gross'],
            'maxdd_pct' => $out['maxdd_pct'],
            'sharpe' => $out['sharpe'],
            'trades' => $out['trades'],
            'avg_trade_net_pct' => $out['avg_trade_net_pct'],
            'avg_trade_gross_pct' => $out['avg_trade_gross_pct'],
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->info($json !== false ? $json : 'JSON encoding failed');

        // Acceptance eşikleri (NET)
        $minPf = (float) config('lab.acceptance.min_pf', 1.2);
        $maxDD = (float) config('lab.acceptance.max_dd_pct', 15.0);
        $minSh = (float) config('lab.acceptance.min_sharpe', 0.8);
        $ok = ($out['pf'] >= $minPf)
            && ($out['maxdd_pct'] <= $maxDD)
            && ($out['sharpe'] !== null && $out['sharpe'] >= $minSh);
        $this->line('acceptance='.($ok ? 'PASS' : 'FAIL'));

        if ((bool) data_get(config('lab.simulation'), 'alerts.acceptance', true)) {
            $dedupKey = 'lab-acceptance-'.$dateString.'-'.date('H').'-'.app()->environment();
            $alerts->send(
                $ok ? 'info' : 'warn',
                $ok ? 'LAB_ACCEPTANCE_PASS' : 'LAB_ACCEPTANCE_FAIL',
                $ok ? 'LAB acceptance criteria PASSED' : 'LAB acceptance criteria FAILED',
                [
                    'as_of' => $dateString,
                    'pf' => $out['pf'],
                    'pf_gross' => $out['pf_gross'],
                    'maxdd_pct' => $out['maxdd_pct'],
                    'sharpe' => $out['sharpe'],
                    'thresholds' => [
                        'pf' => $minPf,
                        'maxdd_pct' => $maxDD,
                        'sharpe' => $minSh,
                    ],
                ],
                dedupKey: $dedupKey
            );
        }

        return self::SUCCESS;
    }
}
