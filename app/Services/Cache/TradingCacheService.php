<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * SaaS-oriented Trading Cache Service
 * Optimized for multi-tenant, high-performance trading operations
 */
class TradingCacheService
{
    private const DEFAULT_TTL = 300; // 5 minutes

    private const MARKET_DATA_TTL = 30; // 30 seconds for market data

    private const AI_DECISION_TTL = 60; // 1 minute for AI decisions

    private const TENANT_PREFIX = 'tenant';

    public function __construct(
        private readonly string $defaultTenant = 'default'
    ) {}

    /**
     * Cache market data with tenant isolation
     */
    public function cacheMarketData(string $symbol, array $data, ?string $tenant = null): void
    {
        $key = $this->buildKey('market', $symbol, $tenant);
        Cache::put($key, $data, self::MARKET_DATA_TTL);

        // Also store in Redis for WebSocket real-time updates
        try {
            Redis::setex(
                "ws:{$key}",
                self::MARKET_DATA_TTL,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Exception $e) {
            // Redis is optional for core functionality
            logger()->warning('Redis cache failed for market data', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cached market data
     */
    public function getMarketData(string $symbol, ?string $tenant = null): ?array
    {
        $key = $this->buildKey('market', $symbol, $tenant);

        return Cache::get($key);
    }

    /**
     * Cache AI consensus decision with deduplication
     */
    public function cacheAiDecision(string $cycleId, array $decision, ?string $tenant = null): void
    {
        $key = $this->buildKey('ai_decision', $cycleId, $tenant);
        Cache::put($key, $decision, self::AI_DECISION_TTL);

        // Store decision count for rate limiting
        $countKey = $this->buildKey('ai_count', date('Y-m-d-H'), $tenant);
        Cache::increment($countKey, 1);
        Cache::put($countKey.'_ttl', true, 3600); // 1 hour expiry
    }

    /**
     * Get cached AI decision
     */
    public function getAiDecision(string $cycleId, ?string $tenant = null): ?array
    {
        $key = $this->buildKey('ai_decision', $cycleId, $tenant);

        return Cache::get($key);
    }

    /**
     * Cache position data for fast lookups
     */
    public function cachePosition(string $symbol, array $position, ?string $tenant = null): void
    {
        $key = $this->buildKey('position', $symbol, $tenant);
        Cache::put($key, $position, self::DEFAULT_TTL);

        // Add to active positions set
        $setKey = $this->buildKey('active_positions', 'set', $tenant);
        Cache::put($setKey, array_unique([
            ...(Cache::get($setKey, [])),
            $symbol,
        ]), self::DEFAULT_TTL);
    }

    /**
     * Get all active positions for a tenant
     */
    public function getActivePositions(?string $tenant = null): array
    {
        $setKey = $this->buildKey('active_positions', 'set', $tenant);
        $symbols = Cache::get($setKey, []);

        $positions = [];
        foreach ($symbols as $symbol) {
            $position = $this->getPosition($symbol, $tenant);
            if ($position) {
                $positions[$symbol] = $position;
            }
        }

        return $positions;
    }

    /**
     * Get cached position
     */
    public function getPosition(string $symbol, ?string $tenant = null): ?array
    {
        $key = $this->buildKey('position', $symbol, $tenant);

        return Cache::get($key);
    }

    /**
     * Clear position cache when closed
     */
    public function clearPosition(string $symbol, ?string $tenant = null): void
    {
        $key = $this->buildKey('position', $symbol, $tenant);
        Cache::forget($key);

        // Remove from active positions set
        $setKey = $this->buildKey('active_positions', 'set', $tenant);
        $symbols = Cache::get($setKey, []);
        $filtered = array_filter($symbols, fn ($s) => $s !== $symbol);
        Cache::put($setKey, array_values($filtered), self::DEFAULT_TTL);
    }

    /**
     * Cache risk metrics with automatic expiry
     */
    public function cacheRiskMetrics(array $metrics, ?string $tenant = null): void
    {
        $key = $this->buildKey('risk_metrics', 'current', $tenant);
        Cache::put($key, [
            'metrics' => $metrics,
            'calculated_at' => now()->toISOString(),
            'expires_at' => now()->addMinutes(5)->toISOString(),
        ], self::DEFAULT_TTL);
    }

    /**
     * Get risk metrics with freshness check
     */
    public function getRiskMetrics(?string $tenant = null): ?array
    {
        $key = $this->buildKey('risk_metrics', 'current', $tenant);
        $cached = Cache::get($key);

        if (! $cached || ! isset($cached['expires_at'])) {
            return null;
        }

        // Check if still fresh
        if (now()->isAfter($cached['expires_at'])) {
            Cache::forget($key);

            return null;
        }

        return $cached['metrics'];
    }

    /**
     * Get AI decision count for rate limiting
     */
    public function getAiDecisionCount(?string $tenant = null): int
    {
        $key = $this->buildKey('ai_count', date('Y-m-d-H'), $tenant);

        return (int) Cache::get($key, 0);
    }

    /**
     * Warm up cache with essential data
     */
    public function warmup(array $symbols, ?string $tenant = null): void
    {
        foreach ($symbols as $symbol) {
            // Pre-cache market data structure
            $marketKey = $this->buildKey('market', $symbol, $tenant);
            if (! Cache::has($marketKey)) {
                Cache::put($marketKey, [
                    'symbol' => $symbol,
                    'price' => 0.0,
                    'timestamp' => now()->timestamp,
                    'warmed_up' => true,
                ], self::MARKET_DATA_TTL);
            }
        }
    }

    /**
     * Clear all cache for a tenant (for testing or tenant cleanup)
     */
    public function clearTenant(?string $tenant = null): void
    {
        $tenant = $tenant ?? $this->defaultTenant;
        $pattern = self::TENANT_PREFIX.":{$tenant}:*";

        try {
            // Use Redis SCAN for efficient pattern-based deletion
            $cursor = 0;
            do {
                $keys = Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);
                if (! empty($keys)) {
                    Redis::del($keys);
                }
            } while ($cursor > 0);
        } catch (\Exception $e) {
            // Fallback to Laravel Cache (less efficient but reliable)
            logger()->info('Using Laravel Cache for tenant cleanup', [
                'tenant' => $tenant,
                'redis_error' => $e->getMessage(),
            ]);

            // This is less efficient but works with all cache drivers
            Cache::flush(); // Last resort - affects all tenants
        }
    }

    /**
     * Build cache key with tenant isolation
     */
    private function buildKey(string $type, string $identifier, ?string $tenant = null): string
    {
        $tenant = $tenant ?? $this->defaultTenant;

        return self::TENANT_PREFIX.":{$tenant}:{$type}:{$identifier}";
    }
}
