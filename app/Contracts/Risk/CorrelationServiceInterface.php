<?php

declare(strict_types=1);

namespace App\Contracts\Risk;

interface CorrelationServiceInterface
{
    /** @return array<string,array<string,float>> simetrik korelasyon matrisi */
    public function matrix(array $symbols, int $bars = 60, string $interval = '5', ?string $category = 'linear'): array;

    public function isHighlyCorrelated(
        array $openSymbols,
        string $candidate,
        float $threshold = 0.85,
        int $bars = 60,
        string $interval = '5',
        ?string $category = 'linear'
    ): bool;

    public function calculateBeta(
        string $symbol,
        int $bars = 60,
        string $interval = '5',
        string $benchmark = 'BTCUSDT',
        ?string $category = 'linear'
    ): float;

    public function checkCorrelation(string $symbol, string $side): array;
}
