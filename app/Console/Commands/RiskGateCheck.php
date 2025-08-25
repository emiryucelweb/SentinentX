<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Risk\CorrelationService;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuard;
use Illuminate\Console\Command;

final class RiskGateCheck extends Command
{
    protected $signature = 'sentx:risk-gate-check {symbol} {entry} {side} {leverage} {stopLoss}';

    protected $description = 'Funding + Korelasyon + Likidasyon mesafesi kompozit riski değerlendirir '
        .'ve JSON çıktılar.';

    public function handle(RiskGuard $risk, FundingGuard $funding, CorrelationService $corr): int
    {
        $symbol = (string) $this->argument('symbol');
        $entry = (float) $this->argument('entry');
        $side = strtoupper((string) $this->argument('side'));
        $lev = (int) $this->argument('leverage');
        $stopLoss = (float) $this->argument('stopLoss');

        $out = $risk->allowOpenWithGuards($symbol, $entry, $side, $lev, $stopLoss, $funding, $corr);
        $this->info(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
