<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Lab\MetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class LabReport extends Command
{
    protected $signature = 'sentx:lab-report {--days=7} {--json}';

    protected $description = 'Son N gün için LAB özet raporu (NET/GROSS PF, MaxDD, Sharpe, trade adetleri).';

    public function handle(MetricsService $metrics): int
    {
        $days = max(1, (int) $this->option('days'));
        $json = (bool) $this->option('json');
        $initialEquity = (float) config('lab.initial_equity', 10000.0);

        $now = CarbonImmutable::now();
        $rows = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $now->subDays($i);
            $out = $metrics->computeDaily($d, $initialEquity);
            $rows[] = [
                'date' => $d->toDateString(),
                'trades' => $out['trades'],
                'pf' => $out['pf'],
                'pf_gross' => $out['pf_gross'],
                'maxdd_pct' => $out['maxdd_pct'],
                'sharpe' => $out['sharpe'],
                'avg_net_pct' => $out['avg_trade_net_pct'],
                'avg_gross_pct' => $out['avg_trade_gross_pct'],
            ];
        }

        if ($json) {
            $this->line(json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->table(
                ['date', 'trades', 'pf', 'pf_gross', 'maxdd_pct', 'sharpe', 'avg_net_pct', 'avg_gross_pct'],
                array_map(fn ($r) => [
                    $r['date'],
                    $r['trades'],
                    number_format((float) $r['pf'], 3),
                    number_format((float) $r['pf_gross'], 3),
                    number_format((float) $r['maxdd_pct'], 2),
                    $r['sharpe'] === null ? '-' : number_format((float) $r['sharpe'], 3),
                    number_format((float) $r['avg_net_pct'], 3),
                    number_format((float) $r['avg_gross_pct'], 3),
                ], $rows)
            );
        }

        return self::SUCCESS;
    }
}
