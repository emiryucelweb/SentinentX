<?php

declare(strict_types=1);

namespace App\Contracts\Lab;

use Carbon\CarbonImmutable;

interface MetricsServiceInterface
{
    /**
     * Gün sonu metrikleri hesaplar (NET baz) ve ek olarak GROSS metrikleri de döner.
     * Dönüş:
     *  - pf:        net (final_equity / initial_equity)
     *  - pf_gross:  gross (final_equity_gross / initial_equity)
     *  - maxdd_pct: net DD%
     *  - sharpe:    net Sharpe (trade-periyodu)
     *  - trades:    adet
     *  - avg_trade_net_pct, avg_trade_gross_pct
     */
    public function computeDaily(CarbonImmutable $day, float $initialEquity): array;
}
