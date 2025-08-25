<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Consensus\ConsensusRepository;
use DateTimeInterface;

final class FakeConsensusRepository implements ConsensusRepository
{
    public int $vetoCount = 0;

    public array $storedDecisions = [];

    public array $rateLimitInfo = [
        'veto_count' => 0,
        'last_veto_time' => null,
        'circuit_breaker_active' => false,
    ];

    public bool $circuitBreakerActive = false;

    public function vetoCountForWindow(string $symbol, DateTimeInterface $from): int
    {
        return $this->vetoCount;
    }

    public function storeDecision(string $symbol, string $cycleUuid, array $decision): void
    {
        $this->storedDecisions[] = [
            'symbol' => $symbol,
            'cycle_uuid' => $cycleUuid,
            'decision' => $decision,
            'stored_at' => now(),
        ];
    }

    public function getRateLimitInfo(string $symbol): array
    {
        return $this->rateLimitInfo;
    }

    public function incrementVetoCount(string $symbol): void
    {
        $this->vetoCount++;
        $this->rateLimitInfo['veto_count'] = $this->vetoCount;
        $this->rateLimitInfo['last_veto_time'] = now()->timestamp;

        if ($this->vetoCount >= 5) {
            $this->rateLimitInfo['circuit_breaker_active'] = true;
            $this->circuitBreakerActive = true;
        }
    }

    public function shouldTriggerCircuitBreaker(string $symbol): bool
    {
        return $this->circuitBreakerActive;
    }

    // Test helpers
    public function setVetoCount(int $count): void
    {
        $this->vetoCount = $count;
        $this->rateLimitInfo['veto_count'] = $count;
    }

    public function setCircuitBreakerActive(bool $active): void
    {
        $this->circuitBreakerActive = $active;
        $this->rateLimitInfo['circuit_breaker_active'] = $active;
    }

    public function reset(): void
    {
        $this->vetoCount = 0;
        $this->storedDecisions = [];
        $this->rateLimitInfo = [
            'veto_count' => 0,
            'last_veto_time' => null,
            'circuit_breaker_active' => false,
        ];
        $this->circuitBreakerActive = false;
    }
}
