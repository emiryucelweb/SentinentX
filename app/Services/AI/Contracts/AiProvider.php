<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Dto\AiDecision;

interface AiProvider
{
    /**
     * @param  array  $payload  Normalized market+context payload to analyze.
     */
    public function analyze(array $payload): AiDecision;
}
