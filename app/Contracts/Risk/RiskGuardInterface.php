<?php

declare(strict_types=1);

namespace App\Contracts\Risk;

interface RiskGuardInterface
{
    /**
     * Temel likidasyon/buffer kontrolü — stop mesafesi yeterli mi?
     */
    public function okToOpen(
        string $symbol,
        float $entry,
        string $side,
        int $leverage,
        float $stopLoss,
        ?float $k = null
    ): array;

    /**
     * Kompozit kapı: (1) likidasyon mesafesi, (2) funding guard, (3) korelasyon guard.
     */
    public function allowOpenWithGuards(
        string $symbol,
        float $entry,
        string $side,
        int $leverage,
        float $stopLoss,
        \App\Services\Risk\FundingGuard $funding,
        \App\Services\Risk\CorrelationService $corr,
        ?float $corrThreshold = null
    ): array;
}
