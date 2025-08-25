<?php

declare(strict_types=1);

namespace App\Services\Logger;

use App\DTO\AiDecision;
use App\Models\AiLog;

final class AiLogCreatorService
{
    public function log(
        string $cycle,
        string $symbol,
        string $provider,
        string $stage,
        AiDecision $d,
        array $snapshot,
        ?array $raw = null,
        ?int $latencyMs = null
    ): void {
        AiLog::create([
            'cycle_uuid' => $cycle,
            'symbol' => $symbol,
            'provider' => $provider,
            'stage' => $stage,
            'action' => $d->action,
            'confidence' => $d->confidence,
            'input_ctx' => $snapshot,
            'raw_output' => $raw,
            'latency_ms' => $latencyMs,
            'reason' => $d->reason,
        ]);
    }
}
