<?php

declare(strict_types=1);

namespace App\Infrastructure\Consensus;

use App\Domain\Consensus\ConsensusRepository;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class EloquentConsensusRepository implements ConsensusRepository
{
    public function vetoCountForWindow(string $symbol, DateTimeInterface $from): int
    {
        return DB::table('consensus_decisions')
            ->where('symbol', $symbol)
            ->where('created_at', '>=', $from)
            ->whereJsonContains('meta->veto_reason', '!=', null)
            ->count();
    }

    public function storeDecision(string $symbol, string $cycleUuid, array $decision): void
    {
        DB::table('consensus_decisions')->insert([
            'symbol' => $symbol,
            'cycle_uuid' => $cycleUuid,
            'final_action' => $decision['action'] ?? 'NONE',
            'final_confidence' => $decision['confidence'] ?? 0,
            'meta' => json_encode($decision['meta'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getRateLimitInfo(string $symbol): array
    {
        $cacheKey = "rate_limit:{$symbol}";

        try {
            $info = Redis::get($cacheKey);

            return $info ? json_decode($info, true) : [
                'veto_count' => 0,
                'last_veto_time' => null,
                'circuit_breaker_active' => false,
            ];
        } catch (\Exception $e) {
            return [
                'veto_count' => 0,
                'last_veto_time' => null,
                'circuit_breaker_active' => false,
                'redis_error' => $e->getMessage(),
            ];
        }
    }

    public function incrementVetoCount(string $symbol): void
    {
        $cacheKey = "rate_limit:{$symbol}";

        try {
            $currentInfo = $this->getRateLimitInfo($symbol);
            $newInfo = [
                'veto_count' => ($currentInfo['veto_count'] ?? 0) + 1,
                'last_veto_time' => now()->timestamp,
                'circuit_breaker_active' => ($currentInfo['veto_count'] ?? 0) >= 5, // 5 veto threshold
            ];

            Redis::setex($cacheKey, 3600, json_encode($newInfo)); // 1 hour TTL
        } catch (\Exception $e) {
            // Log but don't fail - rate limiting is not critical for core functionality
            logger()->warning('Failed to increment veto count for symbol', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function shouldTriggerCircuitBreaker(string $symbol): bool
    {
        $info = $this->getRateLimitInfo($symbol);

        // Circuit breaker triggers after 5 vetoes within 1 hour
        $vetoCount = $info['veto_count'] ?? 0;
        $lastVetoTime = $info['last_veto_time'] ?? 0;
        $timeSinceLastVeto = now()->timestamp - $lastVetoTime;

        return $vetoCount >= 5 && $timeSinceLastVeto < 3600; // 1 hour window
    }
}
