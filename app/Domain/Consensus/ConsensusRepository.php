<?php

declare(strict_types=1);

namespace App\Domain\Consensus;

use DateTimeInterface;

interface ConsensusRepository
{
    /**
     * Get veto count for a symbol within a time window
     */
    public function vetoCountForWindow(string $symbol, DateTimeInterface $from): int;

    /**
     * Store a consensus decision with metadata
     */
    public function storeDecision(string $symbol, string $cycleUuid, array $decision): void;

    /**
     * Get rate limit information for a symbol
     */
    public function getRateLimitInfo(string $symbol): array;

    /**
     * Increment veto count for circuit breaker
     */
    public function incrementVetoCount(string $symbol): void;

    /**
     * Check if circuit breaker should be triggered
     */
    public function shouldTriggerCircuitBreaker(string $symbol): bool;
}
