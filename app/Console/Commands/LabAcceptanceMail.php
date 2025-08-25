<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Notifier\AlertDispatcher;
use App\Services\Lab\MetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class LabAcceptanceMail extends Command
{
    protected $signature = 'sentx:lab-acceptance-mail {--date=}';

    protected $description = 'Günün LAB metriklerini özetler ve e‑posta/kanal üzerinden raporlar (AlertDispatcher).';

    public function handle(MetricsService $metrics, AlertDispatcher $alerts): int
    {
        if (! (bool) config('lab.reporting.mail.enabled', true)) {
            $this->warn('mail reporting disabled');

            return self::SUCCESS;
        }

        $dateOpt = $this->option('date');
        $day = $dateOpt ? CarbonImmutable::parse((string) $dateOpt) : CarbonImmutable::now();
        $dateString = $day->toDateString();

        $initialEquity = (float) config('lab.initial_equity', 10000.0);
        $out = $metrics->computeDaily($day, $initialEquity);

        $minPf = (float) config('lab.acceptance.min_pf', 1.2);
        $maxDD = (float) config('lab.acceptance.max_dd_pct', 15.0);
        $minSh = (float) config('lab.acceptance.min_sharpe', 0.8);
        $ok = ($out['pf'] >= $minPf)
            && ($out['maxdd_pct'] <= $maxDD)
            && ($out['sharpe'] !== null && $out['sharpe'] >= $minSh);

        $level = $ok ? 'info' : 'warn';
        $code = $ok ? 'LAB_DAILY_REPORT_PASS' : 'LAB_DAILY_REPORT_FAIL';
        $title = 'LAB daily report ('.($ok ? 'PASS' : 'FAIL').')';

        $alerts->send($level, $code, $title, [
            'as_of' => $dateString,
            'pf' => $out['pf'],
            'pf_gross' => $out['pf_gross'],
            'maxdd_pct' => $out['maxdd_pct'],
            'sharpe' => $out['sharpe'],
            'trades' => $out['trades'],
            'avg_trade_net_pct' => $out['avg_trade_net_pct'],
            'avg_trade_gross_pct' => $out['avg_trade_gross_pct'],
            'thresholds' => [
                'pf' => $minPf,
                'maxdd_pct' => $maxDD,
                'sharpe' => $minSh,
            ],
        ], dedupKey: 'lab-daily-report-'.$dateString.'-'.app()->environment());

        $this->info('Acceptance mail dispatched for '.$dateString.' status='.($ok ? 'PASS' : 'FAIL'));

        return self::SUCCESS;
    }
}
